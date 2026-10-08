<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berkas_legalitas', function (Blueprint $table) {
            $table->id();
            $table->string('jenis');
            $table->text('keterangan')->nullable();
            $table->string('lampiran');
            $table->timestamps();
        });

        $keuangan = DB::table('menu')->where('title', 'Keuangan')->where('id_parent', 0)->first();
        if (! $keuangan || DB::table('menu')->where('route_name', 'berkas-legalitas.index')->exists()) {
            return;
        }

        $menuId = DB::table('menu')->insertGetId([
            'id_parent' => $keuangan->id,
            'title' => 'Berkas Legalitas',
            'route_name' => 'berkas-legalitas.index',
            'icon' => 'far fa-folder-open',
            'urutan' => (DB::table('menu')->where('id_parent', $keuangan->id)->max('urutan') ?? 0) + 1,
            'lihat' => 1,
            'tambah' => 1,
            'edit' => 1,
            'hapus' => 1,
        ]);

        foreach (DB::table('users')->pluck('id') as $userId) {
            DB::table('hak_akses')->updateOrInsert(
                ['id_user' => $userId, 'id_menu' => $menuId],
                ['lihat' => 1, 'beranda' => 0, 'tambah' => 1, 'edit' => 1, 'hapus' => 1]
            );
        }

        foreach (DB::table('role')->pluck('id') as $roleId) {
            DB::table('role_user')->updateOrInsert(
                ['id_role' => $roleId, 'id_menu' => $menuId],
                ['lihat' => 1, 'beranda' => 0, 'tambah' => 1, 'edit' => 1, 'hapus' => 1]
            );
        }
    }

    public function down(): void
    {
        $menu = DB::table('menu')->where('route_name', 'berkas-legalitas.index')->first();
        if ($menu) {
            DB::table('hak_akses')->where('id_menu', $menu->id)->delete();
            DB::table('role_user')->where('id_menu', $menu->id)->delete();
            DB::table('menu')->where('id', $menu->id)->delete();
        }
        Schema::dropIfExists('berkas_legalitas');
    }
};
