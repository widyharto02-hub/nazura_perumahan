<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listrik_air', function (Blueprint $table) {
            $table->string('norek_listrik', 35)->nullable()->change();
            $table->string('norek_air', 35)->nullable()->change();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Kolom rekening harus tetap nullable agar data bertahap tidak hilang.');
    }
};
