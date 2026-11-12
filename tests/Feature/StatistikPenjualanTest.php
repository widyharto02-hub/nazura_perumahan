<?php

namespace Tests\Feature;

use App\Services\StatistikPenjualanService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StatistikPenjualanTest extends TestCase
{
    public function test_pipeline_uses_active_documents_and_totals_match_all_projects(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('lokasi_kavling', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kavling');
            $table->string('nama_singkat');
            $table->integer('urutan');
            $table->integer('stt_tampil');
        });
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lokasi');
            $table->integer('stt_arsip');
        });
        foreach (['kavling_peta', 'pengajuan_hold'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->integer('id_lokasi');
                $table->integer('stt_reg')->default(0);
            });
        }
        foreach (['sppr', 'ppjb', 'bast', 'wawancara', 'detail_akad'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->integer('id_customer');
                $table->integer('status')->default(1);
                $table->integer('id_akad')->nullable();
            });
        }
        Schema::create('akad', fn (Blueprint $table) => $table->id());
        Schema::create('wawancara_sp3k', function (Blueprint $table) {
            $table->id();
            $table->integer('id_wawancara');
            $table->integer('status');
        });

        foreach ([1, 2] as $id) {
            DB::table('lokasi_kavling')->insert([
                'id' => $id, 'nama_kavling' => 'Project '.$id,
                'nama_singkat' => 'P'.$id, 'urutan' => $id, 'stt_tampil' => $id === 1 ? 1 : 0,
            ]);
            DB::table('kavling_peta')->insert(['id_lokasi' => $id]);
            DB::table('pengajuan_hold')->insert(['id_lokasi' => $id, 'stt_reg' => 0]);
            DB::table('pengajuan_hold')->insert(['id_lokasi' => $id, 'stt_reg' => 2]);
        }
        foreach ([[1, 1, 0], [2, 2, 0], [3, 1, 1], [4, 1, 0]] as [$id, $lokasi, $arsip]) {
            DB::table('customer')->insert(['id' => $id, 'id_lokasi' => $lokasi, 'stt_arsip' => $arsip]);
            foreach (['sppr', 'ppjb', 'bast', 'wawancara'] as $name) {
                DB::table($name)->insert(['id' => $id, 'id_customer' => $id]);
            }
            DB::table('wawancara_sp3k')->insert(['id_wawancara' => $id, 'status' => 1]);
        }
        DB::table('akad')->insert(['id' => 1]);
        DB::table('detail_akad')->insert([
            ['id_customer' => 3, 'status' => 2, 'id_akad' => 1], // Arsip tidak dihitung.
            ['id_customer' => 4, 'status' => 2, 'id_akad' => 1], // Sudah akad keluar dari proses bank.
            ['id_customer' => 1, 'status' => 1, 'id_akad' => 1], // Belum akad.
        ]);

        $stats = app(StatistikPenjualanService::class)->get();

        $this->assertCount(2, $stats['projects']);
        $this->assertSame([
            'booking' => 2, 'sppr' => 3, 'wawancara' => 2, 'acc_bank' => 3,
            'ppjb' => 3, 'akad' => 1, 'bast' => 3,
        ], $stats['totals']);
        foreach ($stats['totals'] as $key => $total) {
            $this->assertSame($total, $stats['projects']->sum($key));
        }
        $this->assertSame(1, $stats['projects']->first()['wawancara']);
        $this->assertSame(1, $stats['projects']->last()['wawancara']);
    }
}
