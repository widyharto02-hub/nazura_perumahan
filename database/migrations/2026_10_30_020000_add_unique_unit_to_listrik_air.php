<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('listrik_air')->select('id_kavling')->groupBy('id_kavling')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Ada data listrik/air ganda untuk satu unit. Gabungkan data tersebut sebelum menjalankan migration; data tidak dihapus otomatis.');
        }
        Schema::table('listrik_air', function (Blueprint $table) {
            $table->unique('id_kavling', 'listrik_air_unit_unique');
        });
    }

    public function down(): void
    {
        Schema::table('listrik_air', function (Blueprint $table) {
            $table->dropUnique('listrik_air_unit_unique');
        });
    }
};
