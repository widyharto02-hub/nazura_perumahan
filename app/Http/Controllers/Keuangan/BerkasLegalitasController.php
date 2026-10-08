<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\BerkasLegalitas;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BerkasLegalitasController extends Controller
{
    use LogAktivitasTrait;

    private const UPLOAD_PATH = 'assets/keuangan/berkas_legalitas/';

    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            return DataTables::of(BerkasLegalitas::latest())
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($permissions) {
                    $detailUrl = route('berkas-legalitas.show', $row->id);
                    $editUrl = route('berkas-legalitas.edit', $row->id);
                    $deleteUrl = route('berkas-legalitas.destroy', $row->id);

                    $button = '<div class="d-flex justify-content-center">';
                    $button .= '<button type="button" class="btn btn-info btn-sm mx-1 detail-button" data-url="' . e($detailUrl) . '">Detail</button>';

                    if ($permissions['edit']) {
                        $button .= '<button type="button" class="btn btn-primary btn-sm mx-1 edit-button" data-url="' . e($editUrl) . '">Edit</button>';
                    }

                    if ($permissions['hapus']) {
                        $button .= '<form action="' . e($deleteUrl) . '" method="POST" class="d-inline">' . csrf_field() . method_field('DELETE') .
                            '<button type="submit" class="btn btn-danger btn-sm mx-1 delete-button">Hapus</button></form>';
                    }

                    return $button . '</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.keuangan.berkas_legalitas.index', compact('permissions'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request, true);
        $data['lampiran'] = $this->storeAttachment($request);

        $berkas = BerkasLegalitas::create($data);
        $this->logCreate('Berkas Legalitas', $berkas->id);

        return response()->json(['status' => 'success']);
    }

    public function show($id)
    {
        return response()->json(['status' => 'success', 'data' => BerkasLegalitas::findOrFail($id)]);
    }

    public function edit($id)
    {
        return response()->json(['status' => 'success', 'data' => BerkasLegalitas::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $berkas = BerkasLegalitas::findOrFail($id);
        $data = $this->validatedData($request, empty($berkas->lampiran));

        if ($request->hasFile('lampiran')) {
            $this->deleteAttachment($berkas->lampiran);
            $data['lampiran'] = $this->storeAttachment($request);
        }

        $berkas->update($data);
        $this->logEdit('Berkas Legalitas', $berkas->id);

        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $berkas = BerkasLegalitas::findOrFail($id);
        $this->deleteAttachment($berkas->lampiran);
        $this->logDelete('Berkas Legalitas', $berkas->id);
        $berkas->delete();

        return response()->json(['status' => 'success']);
    }

    private function validatedData(Request $request, bool $attachmentRequired): array
    {
        $rules = [
            'jenis' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'lampiran' => ($attachmentRequired ? 'required' : 'nullable') . '|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];

        return $request->validate($rules, [
            'jenis.required' => 'Jenis berkas wajib diisi.',
            'lampiran.required' => 'Lampiran wajib diunggah.',
            'lampiran.mimes' => 'Lampiran harus berupa JPG, JPEG, PNG, atau PDF.',
            'lampiran.max' => 'Ukuran lampiran maksimal 5 MB.',
        ]);
    }

    private function storeAttachment(Request $request): string
    {
        $file = $request->file('lampiran');
        $path = public_path(self::UPLOAD_PATH);
        File::ensureDirectoryExists($path);

        $filename = Str::random(25) . '.' . $file->getClientOriginalExtension();
        $file->move($path, $filename);

        return $filename;
    }

    private function deleteAttachment(?string $filename): void
    {
        $path = public_path(self::UPLOAD_PATH . $filename);

        if ($filename && File::exists($path)) {
            File::delete($path);
        }
    }
}
