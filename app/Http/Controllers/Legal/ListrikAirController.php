<?php
namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\KavlingPeta;
use App\Models\ListrikAir;
use App\Models\LokasiKavling;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Database\UniqueConstraintViolationException;
use Yajra\DataTables\Facades\DataTables;

class ListrikAirController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $opd = ListrikAir::orderBy('id', 'desc');

            return DataTables::of($opd)
                ->addIndexColumn()
                ->addColumn('lokasi_rumah', function ($row) {
                    $lokasi  = LokasiKavling::find($row->id_lokasi);
                    $kavling = KavlingPeta::find($row->id_kavling);

                    $namaLokasi  = $lokasi ? $lokasi->nama_kavling : '-';
                    $kodeKavling = $kavling ? $kavling->kode_kavling : '-';

                    return '<div>' . e($namaLokasi) . '</div>' .
                    '<span class="badge badge-info">' . e($kodeKavling) . '</span>';
                })
                ->addColumn('action', function ($row) use ($permissions): string {
                    $editUrl   = route('listrik-air.edit', $row->id);
                    $deleteUrl = route('listrik-air.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-primary btn-sm edit-button" data-id="' . e($row->id) . '" data-url="' . e($editUrl) . '">Edit</button>';
                    }

                    if ($permissions['hapus']) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="delete-button btn btn-danger btn-sm ml-2">Hapus</button></form>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['lokasi_rumah', 'action'])
                ->make(true);
        }
        $lokasi = LokasiKavling::all();

        return view('admin.legal.listrik_air.index', compact('permissions', 'lokasi'));
    }

    public function edit($id)
    {
        $list = ListrikAir::findOrFail($id);

        return response()->json([
            'status'             => 'success',
            'data'               => $list,
        ]);
    }

    private const ATTACHMENTS = [
        'foto_listrik' => 'listrik_1', 'foto_listrik_2' => 'listrik_2',
        'foto_air' => 'air_1', 'foto_air_2' => 'air_2',
    ];

    public function availableUnits(Request $request)
    {
        $request->validate(['id_lokasi' => 'required|integer', 'except_id' => 'nullable|integer']);
        $used = ListrikAir::query()
            ->when($request->filled('except_id'), fn ($query) => $query->where('id', '!=', $request->integer('except_id')))
            ->select('id_kavling');
        return response()->json(KavlingPeta::where('id_lokasi', $request->id_lokasi)
            ->whereNotIn('id', $used)->orderBy('kode_kavling')->get(['id', 'kode_kavling']));
    }

    public function store(Request $request)
    {
        return $this->saveUtilities($request, new ListrikAir());
    }

    public function update(Request $request, $id)
    {
        return $this->saveUtilities($request, ListrikAir::findOrFail($id));
    }

    private function saveUtilities(Request $request, ListrikAir $data)
    {
        $unique = Rule::unique('listrik_air', 'id_kavling');
        if ($data->exists) $unique->ignore($data->id);
        $rules = [
            'id_lokasi' => ['required', 'integer', Rule::exists('lokasi_kavling', 'id')],
            'id_kavling' => ['required', 'integer', $unique,
                Rule::exists('kavling_peta', 'id')->where('id_lokasi', $request->id_lokasi)],
            'norek_listrik' => 'nullable|required_without:norek_air|string|max:35',
            'norek_air' => 'nullable|required_without:norek_listrik|string|max:35',
        ];
        foreach (self::ATTACHMENTS as $field => $folder) {
            $rules[$field] = 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048';
            $rules['remove_'.$field] = 'sometimes|boolean';
        }
        $validated = $request->validate($rules, [
            'id_kavling.unique' => 'Unit ini sudah memiliki data listrik/air. Gunakan menu Edit untuk melengkapinya.',
            'id_kavling.exists' => 'Unit tidak sesuai dengan lokasi yang dipilih.',
            'norek_listrik.required_without' => 'Isi minimal salah satu nomor rekening listrik atau air.',
            'norek_air.required_without' => 'Isi minimal salah satu nomor rekening listrik atau air.',
        ]);

        $newFiles = [];
        $oldFiles = [];
        DB::beginTransaction();
        try {
            $creating = !$data->exists;
            $data->fill(collect($validated)->only(['id_lokasi', 'id_kavling', 'norek_listrik', 'norek_air'])->all());
            foreach (self::ATTACHMENTS as $field => $folder) {
                if (!$request->hasFile($field) && !$request->boolean('remove_'.$field)) continue;
                if ($data->$field) $oldFiles[] = public_path('assets/legal/listrik_air/'.$folder.'/'.basename($data->$field));
                $data->$field = null;
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $name = Str::random(25).'.'.$file->extension();
                    $path = public_path('assets/legal/listrik_air/'.$folder);
                    $file->move($path, $name);
                    $newFiles[] = $path.'/'.$name;
                    $data->$field = $name;
                }
            }
            $data->save();
            $creating ? $this->logCreate('Listrik Air', $data->id) : $this->logEdit('Listrik Air', $data->id);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($newFiles as $path) if (is_file($path)) @unlink($path);
            if ($e instanceof UniqueConstraintViolationException) {
                return response()->json(['errors' => ['id_kavling' => ['Unit ini sudah memiliki data listrik/air. Gunakan menu Edit.']]], 422);
            }
            report($e);
            return response()->json(['message' => 'Data gagal disimpan. Silakan coba lagi.'], 500);
        }
        foreach ($oldFiles as $path) if (is_file($path)) @unlink($path);
        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $data = ListrikAir::findOrFail($id);
        if (! empty($data->foto_listrik) && file_exists(public_path('assets/legal/listrik_air/listrik_1/' . $data->foto_listrik))) {
            unlink(public_path('assets/legal/listrik_air/listrik_1/' . $data->foto_listrik));
        }
        if (! empty($data->foto_listrik_2) && file_exists(public_path('assets/legal/listrik_air/listrik_2/' . $data->foto_listrik_2))) {
            unlink(public_path('assets/legal/listrik_air/listrik_2/' . $data->foto_listrik_2));
        }
        if (! empty($data->foto_air) && file_exists(public_path('assets/legal/listrik_air/air_1/' . $data->foto_air))) {
            unlink(public_path('assets/legal/listrik_air/air_1/' . $data->foto_air));
        }
        if (! empty($data->foto_air_2) && file_exists(public_path('assets/legal/listrik_air/air_2/' . $data->foto_air_2))) {
            unlink(public_path('assets/legal/listrik_air/air_2/' . $data->foto_air_2));
        }

        $this->logDelete('Listrik Air', $data->id);
        $data->delete();

        return response()->json(['status' => 'success']);
    }
}
