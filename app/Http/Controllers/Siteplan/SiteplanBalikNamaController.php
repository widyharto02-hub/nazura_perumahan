<?php

namespace App\Http\Controllers\Siteplan;

use App\Http\Controllers\Controller;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\Pemasukan;
use App\Models\Piutang;
use Illuminate\Support\Facades\DB;
use TCPDF;

class SiteplanBalikNamaController extends Controller
{
    public function index()
    {
        $selection = \App\Services\SiteplanSelection::load(['masterSvg', 'kavlingPeta.customer.progres', 'kavlingPeta.listrikAir']);

        return view('admin.siteplan.siteplan_baliknama.index', $selection);
    }

    public function show($id)
    {
        $data = KavlingPeta::with(['lokasi', 'customer'])->findOrFail($id);

        $tagihanList   = Piutang::where('id_customer', $data->id_customer)->orderBy('id')->get();
        $pemasukanList = Pemasukan::with('kategori')->where('id_customer', $data->id_customer)->get();

        return response()->json([
            'success'         => true,
            'data'            => $data,
            'tagihan'         => $tagihanList,
            'pemasukan'       => $pemasukanList,
            'total_tagihan'   => $tagihanList->sum('nominal'),
            'total_pemasukan' => $pemasukanList->sum('nominal'),
        ]);
    }

    private function generateSVG($id_lokasi, $width = '100%', $height = '100%')
    {
        $lokasi = LokasiKavling::with([
            'masterSvg',
            'kavlingPeta.BalikNama'
        ])->findOrFail($id_lokasi);


        if (! $lokasi->masterSvg) {
            abort(404, "Data master_svg tidak ditemukan");
        }

        ob_start();

        echo str_replace(['[[lebar]]', '[[tinggi]]'], [$width, $height], $lokasi->masterSvg->header_svg);

        foreach ($lokasi->kavlingPeta as $pt) {
             $warna = '#ffffff';

              if ($pt->BalikNama) {
                $warna = '#00e1ff';
            }

            $replacements = [$pt->map, $warna, $pt->matrik, $pt->kode_kavling];

            if ($pt->jenis_map === 'polygon') {
                echo str_replace(['[[1]]', '[[2]]', '[[3]]', '[[4]]'], $replacements, $lokasi->masterSvg->polygon_svg);
            } elseif ($pt->jenis_map === 'path') {
                echo str_replace(['[[1]]', '[[2]]', '[[3]]', '[[4]]'], $replacements, $lokasi->masterSvg->path_svg);
            }
        }

        echo $lokasi->masterSvg->footer_svg;

        return ob_get_clean();
    }

    public function cetakJPG($id_lokasi)
    {
        return app(\App\Services\SiteplanPrint::class)->download(
            $this->generateSVG($id_lokasi), (int) $id_lokasi, 'Balik Nama', 'jpg'
        );
    }

    public function cetakPDF($id_lokasi)
    {
        return app(\App\Services\SiteplanPrint::class)->download(
            $this->generateSVG($id_lokasi), (int) $id_lokasi, 'Balik Nama', 'pdf'
        );
    }


}
