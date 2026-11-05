<?php

namespace Tests\Feature;

use App\Http\Controllers\Keuangan\DashboardKeuanganController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardKeuanganTest extends TestCase
{
    public function test_summary_and_details_use_transaction_location_and_preserve_legacy_locations(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach (['customer', 'hutang'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->integer('id_lokasi')->nullable();
            });
        }
        Schema::create('lokasi_kavling', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kavling');
        });
        Schema::create('kategori_transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
        });
        foreach (['pemasukan', 'pengeluaran'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->integer('id_lokasi')->nullable();
                $table->integer('id_customer')->default(0);
                $table->integer('id_hutang')->default(0);
                $table->integer('id_kategori_transaksi')->default(1);
                $table->date('tanggal');
                $table->integer('nominal');
                $table->string('keterangan')->default('');
            });
        }

        DB::table('lokasi_kavling')->insert([
            ['id' => 1, 'nama_kavling' => 'Perumahan A'],
            ['id' => 2, 'nama_kavling' => 'Perumahan B'],
        ]);
        DB::table('customer')->insert(['id' => 1, 'id_lokasi' => 1]);
        DB::table('hutang')->insert(['id' => 1, 'id_lokasi' => 2]);
        DB::table('kategori_transaksi')->insert(['id' => 1, 'kategori' => 'Pemasukan']);
        foreach ([
            [2, 0, 0, 100], // Pemasukan manual mengikuti lokasi pada form.
            [0, 0, 0, 200], // Pemasukan manual kantor.
            [0, 1, 0, 300], // Nilai bawaan 0 tetap mengikuti lokasi customer.
            [0, 1, 1, 400], // Nilai bawaan 0 mengikuti lokasi hutang terlebih dahulu.
            [null, 0, 0, 500],
        ] as [$lokasi, $customer, $hutang, $nominal]) {
            DB::table('pemasukan')->insert([
                'id_lokasi' => $lokasi, 'id_customer' => $customer,
                'id_hutang' => $hutang, 'nominal' => $nominal, 'tanggal' => '2026-11-05',
            ]);
        }

        $controller = new DashboardKeuanganController;
        foreach (['' => 1500, '0' => 200, '1' => 300, '2' => 500, '-1' => 500] as $lokasi => $total) {
            $filter = ['id_lokasi' => (string) $lokasi];
            $summary = $controller->data(Request::create('/', 'GET', $filter))->getData(true);
            $this->assertSame($total, $summary['total_pemasukan']);
            $detail = $controller->detail(Request::create('/', 'GET', $filter + ['tipe' => 'pemasukan']))->getData(true);
            $detailTotal = array_sum(array_map(fn ($row) => (int) str_replace('.', '', $row['nominal']), $detail['data']));
            $this->assertSame($total, $detailTotal);
            if ((string) $lokasi === '0') {
                $this->assertSame('Office Nazura Group', $summary['proyek'][0]['nama_proyek']);
            }
        }

        $summary = $controller->data(Request::create('/', 'GET', ['tanggal_mulai' => '2026-11-06']))->getData(true);
        $this->assertSame(0, $summary['total_pemasukan']);
    }
}
