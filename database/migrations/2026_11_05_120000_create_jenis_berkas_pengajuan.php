<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_berkas_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('singkatan', 30);
            $table->string('kolom_lama')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('persyaratan_legal', function (Blueprint $table) {
            $table->json('status_berkas_dinamis')->nullable();
        });
        foreach (['IPH', 'SHGB', 'SSP', 'BPHTB', 'SIKUMBANG', 'DAFTAR_SIKASEP', 'FOTO_SIKASEP', 'TRILOGI'] as $index => $column) {
            DB::table('jenis_berkas_pengajuan')->insert([
                'nama' => str_replace('_', ' ', $column),
                'singkatan' => str_replace('_', ' ', $column),
                'kolom_lama' => $column,
                'urutan' => $index + 1,
                'aktif' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $source = DB::table('menu')->where('route_name', 'pengajuan-berkas.index')->first();
        if (! $source) {
            return;
        }
        $menuId = DB::table('menu')->insertGetId([
            'id_parent' => $source->id_parent,
            'title' => 'Jenis Berkas Pengajuan',
            'route_name' => 'jenis-berkas-pengajuan.index',
            'icon' => 'fas fa-list',
            'urutan' => (DB::table('menu')->where('id_parent', $source->id_parent)->max('urutan') ?? 0) + 1,
            'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1,
        ]);
        // Hak mengatur jenis berkas mengikuti hak edit pengajuan berkas.
        foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $owner) {
            foreach (DB::table($table)->where('id_menu', $source->id)->get() as $access) {
                $allowed = (int) $access->edit;
                DB::table($table)->insert([
                    $owner => $access->$owner, 'id_menu' => $menuId,
                    'lihat' => $allowed, 'tambah' => $allowed, 'edit' => $allowed,
                    'hapus' => $allowed, 'beranda' => 0,
                ]);
            }
        }
    }

    public function down(): void
    {
        $menuId = DB::table('menu')->where('route_name', 'jenis-berkas-pengajuan.index')->value('id');
        if ($menuId) {
            DB::table('hak_akses')->where('id_menu', $menuId)->delete();
            DB::table('role_user')->where('id_menu', $menuId)->delete();
            DB::table('menu')->where('id', $menuId)->delete();
        }
        Schema::table('persyaratan_legal', fn (Blueprint $table) => $table->dropColumn('status_berkas_dinamis'));
        Schema::dropIfExists('jenis_berkas_pengajuan');
    }
};
