<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $transaksi = DB::table('menu')->where('title', 'Transaksi')->where('id_parent', 0)->first();
            if (!$transaksi) {
                throw new RuntimeException('Menu Transaksi tidak ditemukan.');
            }
            $children = DB::table('menu')->whereIn('route_name', ['ppjb.index', 'bast.index'])->pluck('id');
            if ($children->count() !== 2) {
                throw new RuntimeException('Menu PPJB dan BAST harus tersedia sebelum dipindahkan.');
            }
            $parentId = DB::table('menu')->where('title', 'Berkas Transaksi')->where('id_parent', 0)->value('id');
            if (!$parentId) {
                DB::table('menu')->where('id_parent', 0)->where('urutan', '>', $transaksi->urutan)->increment('urutan');
                $parentId = DB::table('menu')->insertGetId([
                    'id_parent' => 0,
                    'title' => 'Berkas Transaksi',
                    'route_name' => '#',
                    'icon' => 'fa-folder-open',
                    'urutan' => $transaksi->urutan + 1,
                    'lihat' => 1, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
                ]);
            }

            // Parent access follows existing child access; child permissions stay intact.
            foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $owner) {
                $access = DB::table($table)->whereIn('id_menu', $children)
                    ->select($owner)->selectRaw('MAX(lihat) as lihat')->groupBy($owner)->get();
                foreach ($access as $row) {
                    DB::table($table)->updateOrInsert(
                        [$owner => $row->$owner, 'id_menu' => $parentId],
                        ['lihat' => $row->lihat, 'beranda' => 0, 'tambah' => 0, 'edit' => 0, 'hapus' => 0]
                    );
                }
            }
            foreach (['ppjb.index' => 1, 'bast.index' => 2] as $route => $order) {
                DB::table('menu')->where('route_name', $route)->update(['id_parent' => $parentId, 'urutan' => $order]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $parent = DB::table('menu')->where('title', 'Berkas Transaksi')->where('id_parent', 0)->first();
            $transaksiId = DB::table('menu')->where('title', 'Transaksi')->where('id_parent', 0)->value('id');
            if (!$parent || !$transaksiId) return;
            foreach (['ppjb.index' => 5, 'bast.index' => 7] as $route => $order) {
                DB::table('menu')->where('route_name', $route)->where('id_parent', $parent->id)
                    ->update(['id_parent' => $transaksiId, 'urutan' => $order]);
            }
            DB::table('hak_akses')->where('id_menu', $parent->id)->delete();
            DB::table('role_user')->where('id_menu', $parent->id)->delete();
            DB::table('menu')->where('id', $parent->id)->delete();
            DB::table('menu')->where('id_parent', 0)->where('urutan', '>', $parent->urutan)->decrement('urutan');
        });
    }
};
