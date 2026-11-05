@extends('admin.layout_admin')
@section('content')
    <div class="content-wrapper">
        <section class="content-header"><div class="container-fluid"></div></section>
        <section class="content">
            <div class="container-fluid">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <div class="card">
                    <div class="card-header"><h3 class="card-title font-weight-bold">Jenis Berkas Pengajuan</h3></div>
                    <div class="card-body">
                        <p class="text-muted">Singkatan digunakan sebagai judul kolom Pengajuan Berkas. Jenis yang tidak aktif tidak ditampilkan.</p>
                        @if ($permissions['tambah'])
                            <form action="{{ route('jenis-berkas-pengajuan.store') }}" method="POST" class="mb-4">
                                @csrf
                                <div class="row align-items-end">
                                    <div class="col-md-4 form-group">
                                        <label for="nama_baru">Nama Berkas</label>
                                        <input id="nama_baru" name="nama" class="form-control" value="{{ old('nama') }}" maxlength="150" required>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label for="singkatan_baru">Singkatan</label>
                                        <input id="singkatan_baru" name="singkatan" class="form-control" value="{{ old('singkatan') }}" maxlength="30" required>
                                    </div>
                                    <div class="col-md-2 form-group">
                                        <label for="urutan_baru">Urutan</label>
                                        <input id="urutan_baru" type="number" name="urutan" class="form-control" value="{{ old('urutan', ($jenisBerkas->max('urutan') ?? 0) + 1) }}" min="0" max="9999" required>
                                    </div>
                                    <div class="col-md-1 form-group">
                                        <label><input type="checkbox" name="aktif" value="1" checked> Aktif</label>
                                    </div>
                                    <div class="col-md-2 form-group"><button class="btn btn-primary" type="submit">Tambah</button></div>
                                </div>
                            </form>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead><tr><th>Nama Berkas</th><th>Singkatan</th><th width="100">Urutan</th><th>Aktif</th><th width="180">Action</th></tr></thead>
                                <tbody>
                                    @forelse ($jenisBerkas as $jenis)
                                        <tr>
                                            <td><input aria-label="Nama berkas" form="edit-{{ $jenis->id }}" name="nama" class="form-control" value="{{ $jenis->nama }}" maxlength="150" required @disabled(!$permissions['edit'])></td>
                                            <td><input aria-label="Singkatan" form="edit-{{ $jenis->id }}" name="singkatan" class="form-control" value="{{ $jenis->singkatan }}" maxlength="30" required @disabled(!$permissions['edit'])></td>
                                            <td><input aria-label="Urutan" form="edit-{{ $jenis->id }}" name="urutan" type="number" class="form-control" value="{{ $jenis->urutan }}" min="0" max="9999" required @disabled(!$permissions['edit'])></td>
                                            <td><input aria-label="Aktif" form="edit-{{ $jenis->id }}" name="aktif" type="checkbox" value="1" @checked($jenis->aktif) @disabled(!$permissions['edit'])></td>
                                            <td>
                                                @if ($permissions['edit'])
                                                    <form id="edit-{{ $jenis->id }}" action="{{ route('jenis-berkas-pengajuan.update', $jenis->id) }}" method="POST" class="d-inline">
                                                        @csrf @method('PUT')
                                                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                                    </form>
                                                @endif
                                                @if ($permissions['hapus'])
                                                    <form action="{{ route('jenis-berkas-pengajuan.destroy', $jenis->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jenis berkas ini dari daftar pengajuan?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center">Belum ada jenis berkas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
