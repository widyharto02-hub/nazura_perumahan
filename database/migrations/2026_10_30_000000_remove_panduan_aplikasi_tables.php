<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hak_akses_panduan_apk');
        Schema::dropIfExists('menu_panduan_aplikasi');
    }

    public function down(): void
    {
        throw new RuntimeException('Penghapusan tabel panduan tidak dapat dibatalkan. Pulihkan dari backup database.');
    }
};
