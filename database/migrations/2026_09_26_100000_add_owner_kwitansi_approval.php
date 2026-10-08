<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('konfigurasi', function (Blueprint $table) {
            $table->string('tanda_tangan_owner')->nullable()->after('front_page');
        });

        Schema::table('pemasukan', function (Blueprint $table) {
            $table->timestamp('disetujui_owner_pada')->nullable()->after('keterangan_kategori');
            $table->unsignedBigInteger('disetujui_owner_oleh')->nullable()->after('disetujui_owner_pada');
        });

        DB::transaction(function () {
            $ownerId = DB::table('role')->whereRaw('LOWER(role) = ?', ['owner'])->value('id');
            if (! $ownerId) {
                $ownerId = DB::table('role')->insertGetId(['role' => 'Owner']);
            }

            $keuangan = DB::table('menu')->where('title', 'Keuangan')->where('id_parent', 0)->first();
            if (! $keuangan) {
                return;
            }

            $menuId = DB::table('menu')->where('route_name', 'kwitansi-owner.index')->value('id');
            if (! $menuId) {
                $menuId = DB::table('menu')->insertGetId([
                    'id_parent' => $keuangan->id,
                    'title' => 'Persetujuan Kwitansi',
                    'route_name' => 'kwitansi-owner.index',
                    'icon' => 'fas fa-signature',
                    'urutan' => (DB::table('menu')->where('id_parent', $keuangan->id)->max('urutan') ?? 0) + 1,
                    'lihat' => 1,
                    'tambah' => 0,
                    'edit' => 1,
                    'hapus' => 0,
                ]);
            }

            DB::table('role_user')->updateOrInsert(
                ['id_role' => $ownerId, 'id_menu' => $menuId],
                ['lihat' => 1, 'beranda' => 0, 'tambah' => 0, 'edit' => 1, 'hapus' => 0]
            );

            $dashboardId = DB::table('menu')->where('route_name', 'dashboard-keuangan.index')->value('id');
            if ($dashboardId) {
                DB::table('role_user')->updateOrInsert(
                    ['id_role' => $ownerId, 'id_menu' => $dashboardId],
                    ['lihat' => 1, 'beranda' => 0, 'tambah' => 0, 'edit' => 0, 'hapus' => 0]
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('pemasukan', function (Blueprint $table) {
            $table->dropColumn(['disetujui_owner_pada', 'disetujui_owner_oleh']);
        });
        Schema::table('konfigurasi', function (Blueprint $table) {
            $table->dropColumn('tanda_tangan_owner');
        });

        $menu = DB::table('menu')->where('route_name', 'kwitansi-owner.index')->first();
        if ($menu) {
            DB::table('hak_akses')->where('id_menu', $menu->id)->delete();
            DB::table('role_user')->where('id_menu', $menu->id)->delete();
            DB::table('menu')->where('id', $menu->id)->delete();
        }
    }
};
