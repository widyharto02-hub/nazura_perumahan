<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Models\HakAkses;
use App\Models\JenisBerkasPengajuan;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JenisBerkasPengajuanController extends Controller
{
    use LogAktivitasTrait;

    private function permissions(): array
    {
        $akses = HakAkses::where('id_user', auth()->id())->whereHas('menu', fn ($q) => $q->where('route_name', 'jenis-berkas-pengajuan.index'))->first();
        return collect(['lihat', 'tambah', 'edit', 'hapus'])->mapWithKeys(fn ($key) => [$key => (int) ($akses?->$key ?? 0)])->all();
    }

    public function index()
    {
        $permissions = $this->permissions();
        abort_unless($permissions['lihat'], 403);
        $jenisBerkas = JenisBerkasPengajuan::orderBy('urutan')->orderBy('id')->get();
        return view('admin.legal.jenis_berkas_pengajuan.index', compact('permissions', 'jenisBerkas'));
    }

    private function validated(Request $request, ?JenisBerkasPengajuan $jenis = null): array
    {
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'singkatan' => ['required', 'string', 'max:30', Rule::unique('jenis_berkas_pengajuan', 'singkatan')->whereNull('deleted_at')->ignore($jenis?->id)],
            'urutan' => 'required|integer|min:0|max:9999',
            'aktif' => 'sometimes|boolean',
        ]);
        $data['aktif'] = $request->boolean('aktif');
        return $data;
    }

    public function store(Request $request)
    {
        abort_unless($this->permissions()['tambah'], 403);
        $jenis = JenisBerkasPengajuan::create($this->validated($request));
        $this->logCreate('Jenis Berkas Pengajuan', $jenis->id);
        return redirect()->route('jenis-berkas-pengajuan.index')->with('success', 'Jenis berkas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        abort_unless($this->permissions()['edit'], 403);
        $jenis = JenisBerkasPengajuan::findOrFail($id);
        $jenis->update($this->validated($request, $jenis));
        $this->logEdit('Jenis Berkas Pengajuan', $jenis->id);
        return redirect()->route('jenis-berkas-pengajuan.index')->with('success', 'Jenis berkas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        abort_unless($this->permissions()['hapus'], 403);
        $jenis = JenisBerkasPengajuan::findOrFail($id);
        $jenis->delete();
        $this->logDelete('Jenis Berkas Pengajuan', $jenis->id);
        return redirect()->route('jenis-berkas-pengajuan.index')->with('success', 'Jenis berkas berhasil dihapus.');
    }
}
