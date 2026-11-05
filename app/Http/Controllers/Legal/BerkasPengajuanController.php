<?php
namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\JenisBerkasPengajuan;
use App\Models\PersyaratanLegal;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BerkasPengajuanController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();
        $jenisBerkas = JenisBerkasPengajuan::aktif()->get();

        if ($request->ajax()) {
            $data = PersyaratanLegal::with('customer')->orderBy('id', 'desc');

            $table = DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_customer', function ($row) {
                    return $row->customer?->nama_lengkap ?? '';
                })
                ->addColumn('action', function ($row) use ($permissions): string {
                    $editUrl = route('pengajuan-berkas.edit', $row->id);
                    $hasCust = $row->customer ? true : false;

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-primary btn-sm edit-button ' . ($hasCust ? '' : 'disabled') . '" data-id="' . e($row->id) . '" data-url="' . e($editUrl) . '">Edit</button>';

                    }

                    $btn .= '</div>';
                    return $btn;
                });
            $rawColumns = ['action'];
            foreach ($jenisBerkas as $jenis) {
                $key = 'berkas_' . $jenis->id;
                $rawColumns[] = $key;
                $table->addColumn($key, fn ($row) => $row->statusJenisBerkas($jenis)
                    ? '<i class="fas fa-check-circle text-success"></i>'
                    : '<i class="fas fa-times-circle text-danger"></i>');
            }
            return $table->rawColumns($rawColumns)->make(true);
        }

        return view('admin.legal.pengajuan_berkas.index', compact('permissions', 'jenisBerkas'));
    }

    public function edit($id)
    {
        $list = PersyaratanLegal::with('customer')->findOrFail($id);

        $list->status_berkas = JenisBerkasPengajuan::aktif()->get()->mapWithKeys(fn ($jenis) => [$jenis->id => $list->statusJenisBerkas($jenis)]);

        return response()->json([
            'status' => 'success',
            'data'   => $list,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = PersyaratanLegal::findOrFail($id);

        $jenisBerkas = JenisBerkasPengajuan::aktif()->get();
        $rules = [
            'catatan_kekurangan' => 'nullable|string',
            'percakapan_wa' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ];
        $attributes = [];
        foreach ($jenisBerkas as $jenis) {
            $rules['berkas.' . $jenis->id] = 'required|in:0,1';
            $attributes['berkas.' . $jenis->id] = $jenis->nama;
        }
        $request->validate($rules, [], $attributes);

        DB::beginTransaction();
        try {
            if ($request->hasFile('percakapan_wa')) {
                if (! empty($data->percakapan_wa) && file_exists(public_path('assets/legal/pengajuan_berkas/percakapan_wa/' . $data->percakapan_wa))) {
                    unlink(public_path('assets/legal/pengajuan_berkas/percakapan_wa/' . $data->percakapan_wa));
                }

                $foto             = $request->file('percakapan_wa');
                $ext              = $foto->getClientOriginalExtension();
                $percakapanwaName = Str::random(25) . '.' . $ext;
                $foto->move(public_path('assets/legal/pengajuan_berkas/percakapan_wa/'), $percakapanwaName);
            }

            $data->update([
                'catatan_kekurangan' => $request->catatan_kekurangan ?? '',
                'percakapan_wa'      => isset($percakapanwaName) ? $percakapanwaName : $data->percakapan_wa,
            ]);

            $data->simpanStatusBerkas($jenisBerkas, $request->input('berkas', []));

            $this->logEdit('Berkas Pengajuan', $data->id);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }
}
