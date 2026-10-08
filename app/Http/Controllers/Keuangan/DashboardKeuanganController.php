<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\LokasiKavling;
use App\Models\HakAkses;
use App\Models\Menu;
use App\Models\Pemasukan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardKeuanganController extends Controller
{
    public function index(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai', now()->startOfMonth()->toDateString());
        $tanggalSelesai = $request->input('tanggal_selesai', now()->toDateString());
        $idLokasi = $request->input('id_lokasi');

        $pemasukan = $this->summary('pemasukan', $tanggalMulai, $tanggalSelesai, $idLokasi);
        $pengeluaran = $this->summary('pengeluaran', $tanggalMulai, $tanggalSelesai, $idLokasi);
        $proyek = $this->mergeProjectSummary($pemasukan, $pengeluaran);
        $persetujuanMenuId = Menu::where('route_name', 'kwitansi-owner.index')->value('id');
        $bolehLihatPersetujuan = $persetujuanMenuId && HakAkses::where('id_user', auth()->id())
            ->where('id_menu', $persetujuanMenuId)->where('lihat', 1)->exists();
        $kwitansiMenungguOwner = $bolehLihatPersetujuan
            ? Pemasukan::whereNull('disetujui_owner_pada')->whereNotNull('no_kwitansi')->where('no_kwitansi', '!=', '')->count()
            : 0;

        return view('admin.keuangan.dashboard.index', [
            'lokasiList' => LokasiKavling::orderBy('nama_kavling')->get(),
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'idLokasi' => $idLokasi,
            'totalPemasukan' => $pemasukan->sum('total'),
            'totalPengeluaran' => $pengeluaran->sum('total'),
            'proyek' => $proyek,
            'bolehLihatPersetujuan' => $bolehLihatPersetujuan,
            'kwitansiMenungguOwner' => $kwitansiMenungguOwner,
        ]);
    }

    public function detail(Request $request)
    {
        $request->validate([
            'tipe' => 'required|in:pemasukan,pengeluaran',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'id_lokasi' => 'nullable|integer',
        ]);

        $data = DB::table($request->tipe . ' as transaksi')
            ->leftJoin('kategori_transaksi as kategori', 'kategori.id', '=', 'transaksi.id_kategori_transaksi')
            ->whereBetween('transaksi.tanggal', [$request->tanggal_mulai, $request->tanggal_selesai])
            ->when((string) $request->id_lokasi === '0', fn ($query) => $query->where('transaksi.id_lokasi', 0))
            ->when((string) $request->id_lokasi === '-1', fn ($query) => $query->whereNull('transaksi.id_lokasi'))
            ->when($request->filled('id_lokasi') && ! in_array((string) $request->id_lokasi, ['0', '-1'], true), fn ($query) => $query->where('transaksi.id_lokasi', $request->id_lokasi))
            ->orderByDesc('transaksi.tanggal')
            ->select('transaksi.tanggal', 'transaksi.nominal', 'transaksi.keterangan', 'kategori.kategori')
            ->get()
            ->map(fn ($row) => [
                'tanggal' => Carbon::parse($row->tanggal)->translatedFormat('d F Y'),
                'kategori' => $row->kategori ?? '-',
                'keterangan' => $row->keterangan ?: '-',
                'nominal' => number_format($row->nominal, 0, ',', '.'),
            ]);

        return response()->json(['data' => $data]);
    }

    private function summary(string $table, string $tanggalMulai, string $tanggalSelesai, $idLokasi)
    {
        return DB::table($table . ' as transaksi')
            ->leftJoin('lokasi_kavling as lokasi', 'lokasi.id', '=', 'transaksi.id_lokasi')
            ->whereBetween('transaksi.tanggal', [$tanggalMulai, $tanggalSelesai])
            ->when((string) $idLokasi === '0', fn ($query) => $query->where('transaksi.id_lokasi', 0))
            ->when((string) $idLokasi === '-1', fn ($query) => $query->whereNull('transaksi.id_lokasi'))
            ->when($idLokasi !== null && $idLokasi !== '' && ! in_array((string) $idLokasi, ['0', '-1'], true), fn ($query) => $query->where('transaksi.id_lokasi', $idLokasi))
            ->selectRaw("CASE WHEN transaksi.id_lokasi = 0 THEN 0 WHEN transaksi.id_lokasi IS NULL THEN -1 ELSE lokasi.id END as id_lokasi, CASE WHEN transaksi.id_lokasi = 0 THEN 'Office Nazura Group' WHEN transaksi.id_lokasi IS NULL THEN 'Tanpa Proyek' ELSE lokasi.nama_kavling END as nama_proyek, SUM(transaksi.nominal) as total")
            ->groupByRaw('transaksi.id_lokasi, lokasi.id, lokasi.nama_kavling')
            ->get();
    }

    private function mergeProjectSummary($pemasukan, $pengeluaran)
    {
        $summary = [];

        foreach ($pemasukan as $item) {
            $summary[$item->id_lokasi] = ['id_lokasi' => $item->id_lokasi, 'nama_proyek' => $item->nama_proyek, 'pemasukan' => (int) $item->total, 'pengeluaran' => 0];
        }
        foreach ($pengeluaran as $item) {
            $summary[$item->id_lokasi] ??= ['id_lokasi' => $item->id_lokasi, 'nama_proyek' => $item->nama_proyek, 'pemasukan' => 0, 'pengeluaran' => 0];
            $summary[$item->id_lokasi]['pengeluaran'] = (int) $item->total;
        }

        return collect($summary)->map(function ($item) {
            $item['selisih'] = $item['pemasukan'] - $item['pengeluaran'];
            return $item;
        })->sortBy('nama_proyek')->values();
    }
}
