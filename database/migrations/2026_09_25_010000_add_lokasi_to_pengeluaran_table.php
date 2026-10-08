<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->unsignedBigInteger('id_lokasi')->nullable()->after('id_bank');
        });
    }

    public function down(): void
    {
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropColumn('id_lokasi');
        });
    }
};
