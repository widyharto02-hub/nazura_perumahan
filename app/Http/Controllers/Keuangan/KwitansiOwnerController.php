<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\HakAkses;
use App\Models\Menu;
use App\Models\Pemasukan;
use App\Models\PengaturanMedia;
use App\Models\Role;
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\Facades\DataTables;

class KwitansiOwnerController extends Controller
{
    use LogAktivitasTrait;

    public function index(Request $request)
    {
        $permissions = $this->approvalPermissions();
        abort_unless($permissions['lihat'] ?? 0, 403);
        $isOwner = $this->isOwner();

        if ($request->ajax()) {
            $data = Pemasukan::with(['customer', 'kategori'])
                ->whereNull('disetujui_owner_pada')
                ->whereNotNull('no_kwitansi')
                ->where('no_kwitansi', '!=', '')
                ->orderByDesc('tanggal');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('tanggal', fn (Pemasukan $row) => Carbon::parse($row->tanggal)->translatedFormat('d F Y'))
                ->addColumn('customer', fn (Pemasukan $row) => $row->customer->nama_lengkap ?? '-')
                ->addColumn('kategori', fn (Pemasukan $row) => $row->kategori->kategori ?? '-')
                ->editColumn('nominal', fn (Pemasukan $row) => 'Rp ' . number_format($row->nominal, 0, ',', '.'))
                ->addColumn('action', function (Pemasukan $row) use ($permissions, $isOwner) {
                    $detail = '<button type="button" class="btn btn-info btn-sm btn-detail-owner mr-1" data-url="' . e(route('kwitansi-owner.show', $row->id)) . '"><i class="fas fa-eye"></i> Detail</button>';
                    if (! $isOwner || ! ($permissions['edit'] ?? 0)) {
                        return $detail;
                    }

                    return $detail . '<form method="POST" action="' . e(route('kwitansi-owner.setujui', $row->id)) . '" class="d-inline form-setujui-owner">'
                        . csrf_field()
                        . '<button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Setujui</button></form>';
                })
                ->rawColumns(['nominal', 'action'])
                ->make(true);
        }

        return view('admin.keuangan.kwitansi_owner.index', compact('permissions', 'isOwner'));
    }

    public function show(int $id)
    {
        $permissions = $this->approvalPermissions();
        abort_unless($permissions['lihat'] ?? 0, 403);

        $pemasukan = Pemasukan::with(['customer', 'kategori', 'bank', 'metode'])
            ->whereNotNull('no_kwitansi')
            ->where('no_kwitansi', '!=', '')
            ->findOrFail($id);

        return view('admin.keuangan.kwitansi_owner.detail', compact('pemasukan'));
    }

    public function setujui(int $id)
    {
        $permissions = $this->approvalPermissions();
        $this->authorizeOwner(($permissions['lihat'] ?? 0) && ($permissions['edit'] ?? 0));

        $tandaTangan = PengaturanMedia::where('jenis_data', 'Tanda Tangan Owner')->where('stt_aktif', 1)->value('nama_file');
        abort_if(! $tandaTangan || ! File::exists(public_path('config_media/' . $tandaTangan)), 422, 'Tanda tangan owner belum diunggah pada Pengaturan Media.');

        $pemasukan = Pemasukan::whereNull('disetujui_owner_pada')->findOrFail($id);
        $pemasukan->update([
            'disetujui_owner_pada' => now(),
            'disetujui_owner_oleh' => Auth::id(),
        ]);
        $this->logEdit('Persetujuan Kwitansi Owner', $pemasukan->id);

        return response()->json(['success' => true, 'message' => 'Kwitansi telah disetujui owner.']);
    }

    private function authorizeOwner(bool|int $hasPermission): void
    {
        abort_unless($hasPermission && $this->isOwner(), 403);
    }

    private function isOwner(): bool
    {
        $role = Role::find(Auth::user()->id_role ?? 0);

        return strtolower($role->role ?? '') === 'owner';
    }

    private function approvalPermissions(): array
    {
        $menuId = Menu::where('route_name', 'kwitansi-owner.index')->value('id');
        $akses = $menuId
            ? HakAkses::where('id_user', Auth::id())->where('id_menu', $menuId)->first()
            : null;

        return [
            'lihat' => $akses->lihat ?? 0,
            'edit' => $akses->edit ?? 0,
        ];
    }
}
