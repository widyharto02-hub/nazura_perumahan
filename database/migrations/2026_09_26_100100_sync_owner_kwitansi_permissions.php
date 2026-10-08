<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ownerId = DB::table('role')->whereRaw('LOWER(role) = ?', ['owner'])->value('id');
        if (! $ownerId) {
            return;
        }

        $menuIds = DB::table('menu')->whereIn('route_name', [
            'kwitansi-owner.index',
            'dashboard-keuangan.index',
        ])->pluck('id');
        $keuanganId = DB::table('menu')->where('title', 'Keuangan')->where('id_parent', 0)->value('id');
        if ($keuanganId) {
            $menuIds->push($keuanganId);
        }

        foreach ($menuIds->unique() as $menuId) {
            $isApproval = $menuId == DB::table('menu')->where('route_name', 'kwitansi-owner.index')->value('id');
            $access = ['lihat' => 1, 'beranda' => 0, 'tambah' => 0, 'edit' => $isApproval ? 1 : 0, 'hapus' => 0];
            DB::table('role_user')->updateOrInsert(['id_role' => $ownerId, 'id_menu' => $menuId], $access);

            foreach (DB::table('users')->where('id_role', $ownerId)->pluck('id') as $userId) {
                DB::table('hak_akses')->updateOrInsert(['id_user' => $userId, 'id_menu' => $menuId], $access);
            }
        }
    }

    public function down(): void
    {
        // Hak akses yang sudah disesuaikan pengguna tidak dihapus saat rollback.
    }
};
