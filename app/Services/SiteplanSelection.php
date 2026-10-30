<?php

namespace App\Services;

use App\Models\LokasiKavling;

class SiteplanSelection
{
    public const ROUTES = [
        'siteplan-penjualan.index',
        'st-balik-nama.index',
        'st-bphtb-ssp.index',
        'siteplan-air.index',
        'siteplan-listrik.index',
    ];

    public static function navigation(): \Illuminate\Support\Collection
    {
        return collect(session('getmenus', []))
            ->flatMap(fn ($menu) => collect([$menu])->concat($menu->children ?? []))
            ->filter(fn ($menu) => in_array($menu->route_name, self::ROUTES, true)
                && \Illuminate\Support\Facades\Route::has($menu->route_name))
            ->unique('route_name')->values();
    }

    public static function load(array $relations): array
    {
        $input = request()->validate(['id_lokasi' => ['nullable', 'integer', 'min:1']]);
        $lokasiOptions = LokasiKavling::orderBy('urutan')->orderBy('id')->get(['id', 'nama_kavling']);
        $selectedId = $input['id_lokasi'] ?? $lokasiOptions->first()?->id;
        $selectedLocation = null;
        if ($selectedId !== null) {
            $selectedLocation = LokasiKavling::with($relations)->findOrFail($selectedId);
        }

        return [
            'lokasiOptions' => $lokasiOptions,
            'selectedLocation' => $selectedLocation,
            'lokasiKavling' => $selectedLocation ? collect([$selectedLocation]) : collect(),
        ];
    }
}
