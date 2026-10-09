<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\LokasiKavling;
use App\Models\HakAkses;
use App\Models\Menu;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardKeuanganController extends Controller
{
    public function index(Request $request)
    {
        // Periode default kosong (null) artinya seluruh waktu
        $tanggalMulai = $request->input('tanggal_mulai', '');
        $tanggalSelesai = $request->input('tanggal_selesai', '');
        $idLokasi = $request->input('id_lokasi', '');

        // Ringkasan dimuat penuh via AJAX (method data), halaman awal hanya kerangka filter
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
            'bolehLihatPersetujuan' => $bolehLihatPersetujuan,
            'kwitansiMenungguOwner' => $kwitansiMenungguOwner,
        ]);
    }

    public function detail(Request $request)
    {
        $request->validate($this->aturanFilter($request, true), [
            'tipe.required' => 'Jenis transaksi wajib dipilih.',
            'tipe.in' => 'Jenis transaksi tidak valid.',
            'tanggal_mulai.date' => 'Periode mulai harus berupa tanggal yang valid.',
            'tanggal_selesai.date' => 'Periode selesai harus berupa tanggal yang valid.',
            'tanggal_selesai.after_or_equal' => 'Periode selesai tidak boleh sebelum periode mulai.',
        ]);

        $idLokasi = $request->id_lokasi;
        $tanggalMulai = $request->tanggal_mulai ?: null;
        $tanggalSelesai = $request->tanggal_selesai ?: null;

        if ($request->tipe === 'pemasukan') {
            // Lokasi pemasukan mengikuti customer; filter Office hanya mengambil pengeluaran
            if ((string) $idLokasi === '0') {
                return response()->json(['data' => []]);
            }

            $query = Pemasukan::query()->from('pemasukan as transaksi')
                ->leftJoin('kategori_transaksi as kategori', 'kategori.id', '=', 'transaksi.id_kategori_transaksi')
                ->leftJoin('customer as pelanggan', 'pelanggan.id', '=', 'transaksi.id_customer')
                ->when($tanggalMulai && $tanggalSelesai, fn ($query) => $query->whereBetween('transaksi.tanggal', [$tanggalMulai, $tanggalSelesai]))
                ->when($tanggalMulai && ! $tanggalSelesai, fn ($query) => $query->where('transaksi.tanggal', '>=', $tanggalMulai))
                ->when(! $tanggalMulai && $tanggalSelesai, fn ($query) => $query->where('transaksi.tanggal', '<=', $tanggalSelesai))
                ->when((string) $idLokasi === '-1', fn ($query) => $query->whereNull('pelanggan.id_lokasi'))
                ->when($request->filled('id_lokasi') && ! in_array((string) $idLokasi, ['0', '-1'], true), fn ($query) => $query->where('pelanggan.id_lokasi', $idLokasi));
        } else {
            // Lokasi pengeluaran langsung dari kolom id_lokasi transaksi
            $query = Pengeluaran::query()->from('pengeluaran as transaksi')
                ->leftJoin('kategori_transaksi as kategori', 'kategori.id', '=', 'transaksi.id_kategori_transaksi')
                ->when($tanggalMulai && $tanggalSelesai, fn ($query) => $query->whereBetween('transaksi.tanggal', [$tanggalMulai, $tanggalSelesai]))
                ->when($tanggalMulai && ! $tanggalSelesai, fn ($query) => $query->where('transaksi.tanggal', '>=', $tanggalMulai))
                ->when(! $tanggalMulai && $tanggalSelesai, fn ($query) => $query->where('transaksi.tanggal', '<=', $tanggalSelesai))
                ->when((string) $idLokasi === '0', fn ($query) => $query->where('transaksi.id_lokasi', 0))
                ->when((string) $idLokasi === '-1', fn ($query) => $query->whereNull('transaksi.id_lokasi'))
                ->when($request->filled('id_lokasi') && ! in_array((string) $idLokasi, ['0', '-1'], true), fn ($query) => $query->where('transaksi.id_lokasi', $idLokasi));
        }

        $data = $query->orderByDesc('transaksi.tanggal')
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

    public function data(Request $request)
    {
        $filter = $request->validate($this->aturanFilter($request), [
            'tanggal_mulai.date' => 'Periode mulai harus berupa tanggal yang valid.',
            'tanggal_selesai.date' => 'Periode selesai harus berupa tanggal yang valid.',
            'tanggal_selesai.after_or_equal' => 'Periode selesai tidak boleh sebelum periode mulai.',
        ]);

        $idLokasi = $filter['id_lokasi'] ?? '';
        $tanggalMulai = $filter['tanggal_mulai'] ?? null;
        $tanggalSelesai = $filter['tanggal_selesai'] ?? null;

        $pemasukan = $this->summaryPemasukan($tanggalMulai, $tanggalSelesai, $idLokasi);
        $pengeluaran = $this->summaryPengeluaran($tanggalMulai, $tanggalSelesai, $idLokasi);
        $proyek = $this->mergeProjectSummary($pemasukan, $pengeluaran);

        return response()->json([
            'total_pemasukan' => (int) $pemasukan->sum('total'),
            'total_pengeluaran' => (int) $pengeluaran->sum('total'),
            'proyek' => $proyek,
        ]);
    }

    private function summaryPemasukan($tanggalMulai, $tanggalSelesai, $idLokasi)
    {
        // Filter Office Nazura Group hanya mengambil pengeluaran, pemasukan diabaikan
        if ((string) $idLokasi === '0') {
            return collect();
        }

        // Lokasi pemasukan mengikuti customer: pemasukan -> customer -> lokasi
        return Pemasukan::query()->from('pemasukan as transaksi')
            ->leftJoin('customer as pelanggan', 'pelanggan.id', '=', 'transaksi.id_customer')
            ->leftJoin('lokasi_kavling as lokasi', 'lokasi.id', '=', 'pelanggan.id_lokasi')
            ->when($tanggalMulai && $tanggalSelesai, fn ($query) => $query->whereBetween('transaksi.tanggal', [$tanggalMulai, $tanggalSelesai]))
            ->when($tanggalMulai && ! $tanggalSelesai, fn ($query) => $query->where('transaksi.tanggal', '>=', $tanggalMulai))
            ->when(! $tanggalMulai && $tanggalSelesai, fn ($query) => $query->where('transaksi.tanggal', '<=', $tanggalSelesai))
            ->when((string) $idLokasi === '-1', fn ($query) => $query->whereNull('pelanggan.id_lokasi'))
            ->when($idLokasi !== null && $idLokasi !== '' && ! in_array((string) $idLokasi, ['0', '-1'], true), fn ($query) => $query->where('pelanggan.id_lokasi', $idLokasi))
            ->selectRaw("CASE WHEN pelanggan.id_lokasi IS NULL THEN -1 ELSE pelanggan.id_lokasi END as id_lokasi, CASE WHEN lokasi.id IS NULL THEN 'Tanpa Proyek' ELSE lokasi.nama_kavling END as nama_proyek, SUM(transaksi.nominal) as total")
            ->groupByRaw('pelanggan.id_lokasi, lokasi.id, lokasi.nama_kavling')
            ->get();
    }

    private function summaryPengeluaran($tanggalMulai, $tanggalSelesai, $idLokasi)
    {
        // Lokasi pengeluaran langsung dari kolom id_lokasi transaksi (0 = Office Nazura Group)
        return Pengeluaran::query()->from('pengeluaran as transaksi')
            ->leftJoin('lokasi_kavling as lokasi', 'lokasi.id', '=', 'transaksi.id_lokasi')
            ->when($tanggalMulai && $tanggalSelesai, fn ($query) => $query->whereBetween('transaksi.tanggal', [$tanggalMulai, $tanggalSelesai]))
            ->when($tanggalMulai && ! $tanggalSelesai, fn ($query) => $query->where('transaksi.tanggal', '>=', $tanggalMulai))
            ->when(! $tanggalMulai && $tanggalSelesai, fn ($query) => $query->where('transaksi.tanggal', '<=', $tanggalSelesai))
            ->when((string) $idLokasi === '0', fn ($query) => $query->where('transaksi.id_lokasi', 0))
            ->when((string) $idLokasi === '-1', fn ($query) => $query->whereNull('transaksi.id_lokasi'))
            ->when($idLokasi !== null && $idLokasi !== '' && ! in_array((string) $idLokasi, ['0', '-1'], true), fn ($query) => $query->where('transaksi.id_lokasi', $idLokasi))
            ->selectRaw("CASE WHEN transaksi.id_lokasi = 0 THEN 0 WHEN transaksi.id_lokasi IS NULL THEN -1 ELSE lokasi.id END as id_lokasi, CASE WHEN transaksi.id_lokasi = 0 THEN 'Office Nazura Group' WHEN transaksi.id_lokasi IS NULL THEN 'Tanpa Proyek' ELSE lokasi.nama_kavling END as nama_proyek, SUM(transaksi.nominal) as total")
            ->groupByRaw('transaksi.id_lokasi, lokasi.id, lokasi.nama_kavling')
            ->get();
    }

    private function aturanFilter(Request $request, $denganTipe = false)
    {
        $aturan = [
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'id_lokasi' => 'nullable',
        ];

        if ($denganTipe) {
            $aturan = ['tipe' => 'required|in:pemasukan,pengeluaran'] + $aturan;
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $aturan['tanggal_selesai'] .= '|after_or_equal:tanggal_mulai';
        }

        return $aturan;
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
