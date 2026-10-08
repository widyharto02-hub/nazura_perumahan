<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $legalitas = DB::table('menu')->where('route_name', 'berkas-legalitas.index')->first();
            if ($legalitas) {
                DB::table('menu')->where('id', $legalitas->id)->update([
                    'id_parent' => 0,
                    'urutan' => 15,
                    'icon' => 'far fa-folder-open',
                ]);
            }

            $dashboard = DB::table('menu')->where('route_name', 'dashboard-keuangan.index')->first();
            if (! $dashboard) {
                DB::table('menu')->where('id_parent', 0)->where('urutan', '>=', 2)->increment('urutan');
                $dashboardId = DB::table('menu')->insertGetId([
                    'id_parent' => 0, 'title' => 'Dashboard Keuangan', 'route_name' => 'dashboard-keuangan.index',
                    'icon' => 'fas fa-chart-pie', 'urutan' => 2, 'lihat' => 1, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
                ]);

                foreach (DB::table('users')->pluck('id') as $userId) {
                    DB::table('hak_akses')->updateOrInsert(['id_user' => $userId, 'id_menu' => $dashboardId], ['lihat' => 1, 'beranda' => 0, 'tambah' => 0, 'edit' => 0, 'hapus' => 0]);
                }
                foreach (DB::table('role')->pluck('id') as $roleId) {
                    DB::table('role_user')->updateOrInsert(['id_role' => $roleId, 'id_menu' => $dashboardId], ['lihat' => 1, 'beranda' => 0, 'tambah' => 0, 'edit' => 0, 'hapus' => 0]);
                }
            }
        });
    }

    public function down(): void
    {
        $dashboard = DB::table('menu')->where('route_name', 'dashboard-keuangan.index')->first();
        if ($dashboard) {
            DB::table('hak_akses')->where('id_menu', $dashboard->id)->delete();
            DB::table('role_user')->where('id_menu', $dashboard->id)->delete();
            DB::table('menu')->where('id', $dashboard->id)->delete();
        }
    }
};
