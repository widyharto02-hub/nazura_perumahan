<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('konfigurasi_media')->where('jenis_data', 'Tanda Tangan Owner')->exists()) {
            DB::table('konfigurasi_media')->insert([
                'jenis_data' => 'Tanda Tangan Owner',
                'keterangan' => 'Tanda tangan yang dicetak pada kwitansi setelah disetujui Owner.',
                'nama_file' => '',
                'urutan' => (DB::table('konfigurasi_media')->max('urutan') ?? 0) + 1,
                'jenis_download' => 0,
                'stt_aktif' => 1,
            ]);
        }

        if (Schema::hasColumn('konfigurasi', 'tanda_tangan_owner')) {
            Schema::table('konfigurasi', function (Blueprint $table) {
                $table->dropColumn('tanda_tangan_owner');
            });
        }
    }

    public function down(): void
    {
        DB::table('konfigurasi_media')->where('jenis_data', 'Tanda Tangan Owner')->delete();
    }
};
