<?php

namespace Tests\Feature;

use App\Http\Controllers\PembayaranController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PencairanKprTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->integer('estimasi_plafon');
        });
        Schema::create('kategori_transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
        });
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->id();
            foreach (['id_customer', 'id_bank', 'id_piutang', 'id_kategori_transaksi', 'nominal', 'id_metode_bayar'] as $column) {
                $table->integer($column);
            }
            foreach (['tanggal', 'no_kwitansi', 'keterangan', 'keterangan_kategori', 'lampiran'] as $column) {
                $table->string($column);
            }
        });
        Schema::create('piutang', function (Blueprint $table) {
            $table->id();
            $table->integer('id_customer');
            $table->integer('nominal');
        });
        DB::table('customer')->insert(['id' => 63, 'estimasi_plafon' => 160340000]);
    }

    private function submit(array $retensi = [])
    {
        return app(PembayaranController::class)->tambahPencairanKpr(Request::create('/', 'POST', [
            'tanggal_pencairan' => '2026-11-12',
            'jumlah_plafon' => '160.340.000',
            'jumlah_pencairan' => $retensi ? '130.696.376' : '160.340.000',
            'retensi' => $retensi,
        ]), 63);
    }

    public function test_add_uses_kpr_category_and_saves_payment(): void
    {
        DB::table('kategori_transaksi')->insert(['id' => 27, 'kategori' => 'Pencairan KPR']);
        $response = $this->submit();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertDatabaseHas('pemasukan', ['id_customer' => 63, 'id_kategori_transaksi' => 27, 'nominal' => 160340000]);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_missing_category_explains_failure_without_saving(): void
    {
        $response = $this->submit();
        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('Kategori transaksi Pencairan KPR belum tersedia', $response->getData(true)['message']);
        $this->assertDatabaseCount('pemasukan', 0);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_retention_failure_reports_stage_and_rolls_back_payment(): void
    {
        DB::table('kategori_transaksi')->insert(['id' => 27, 'kategori' => 'Pencairan KPR']);
        // Missing retention table simulates a storage failure after the payment insert.
        $response = $this->submit([9 => '29.643.624']);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('menyimpan retensi ID 9', $response->getData(true)['message']);
        $this->assertStringNotContainsString('SQLSTATE', $response->getData(true)['message']);
        $this->assertDatabaseCount('pemasukan', 0);
        $this->assertSame(0, DB::transactionLevel());
    }
}
