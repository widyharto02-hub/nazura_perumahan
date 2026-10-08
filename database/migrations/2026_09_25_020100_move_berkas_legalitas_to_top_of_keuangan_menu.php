<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menu = DB::table('menu')->where('route_name', 'berkas-legalitas.index')->first();

        if (! $menu) {
            return;
        }

        DB::transaction(function () use ($menu) {
            DB::table('menu')
                ->where('id_parent', $menu->id_parent)
                ->where('id', '!=', $menu->id)
                ->where('urutan', '>=', 1)
                ->increment('urutan');

            DB::table('menu')->where('id', $menu->id)->update(['urutan' => 1]);
        });
    }

    public function down(): void
    {
        DB::table('menu')->where('route_name', 'berkas-legalitas.index')->update(['urutan' => 9]);
    }
};
