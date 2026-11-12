<?php

namespace App\Services;

use App\Models\AkadDetail;
use App\Models\BAST;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\PengajuanHold;
use App\Models\PPJB;
use App\Models\SPPR;
use App\Models\Wawancara;
use App\Models\WawancaraSp3k;

class StatistikPenjualanService
{
    public const LABELS = [
        'booking' => 'Booking',
        'sppr' => 'Proses Admin',
        'wawancara' => 'Proses Bank',
        'acc_bank' => 'SP3K',
        'ppjb' => 'PPJB',
        'akad' => 'Akad',
        'bast' => 'BAST',
    ];

    public function get(): array
    {
        $projects = LokasiKavling::orderBy('urutan')->orderBy('id')->get()
            ->map(function (LokasiKavling $lokasi) {
                $customer = fn ($query) => $query->where('id_lokasi', $lokasi->id)->where('stt_arsip', 0);

                return [
                    'id' => $lokasi->id,
                    'nama' => $lokasi->nama_kavling,
                    'kode' => $lokasi->nama_singkat,
                    'total_unit' => KavlingPeta::where('id_lokasi', $lokasi->id)->count(),
                    'booking' => PengajuanHold::where('id_lokasi', $lokasi->id)->where('stt_reg', '!=', 2)->count(),
                    'sppr' => SPPR::whereHas('customer', $customer)->count(),
                    'wawancara' => Wawancara::prosesBankAktif()->whereHas('customer', $customer)->count(),
                    'acc_bank' => WawancaraSp3k::where('status', 1)->whereHas('wawancara.customer', $customer)->count(),
                    'ppjb' => PPJB::whereHas('customer', $customer)->count(),
                    'akad' => AkadDetail::where('status', 2)->whereHas('customer', $customer)->count(),
                    'bast' => BAST::whereHas('customer', $customer)->count(),
                ];
            });

        $totals = [];
        foreach (array_keys(self::LABELS) as $key) {
            $totals[$key] = $projects->sum($key);
        }

        return compact('projects', 'totals');
    }
}
