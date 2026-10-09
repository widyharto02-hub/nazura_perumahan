@extends('admin.layout_admin')

@section('content')
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><h1>Edit Proses Admin</h1></div></section>
    <section class="content"><div class="container-fluid"><div class="card"><div class="card-body">
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form action="{{ route('proses-admin.update', $sppr->id) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            @php($customer = $sppr->customer)
            <div class="row">
            <div class="col-lg-6 pr-lg-4 booking-data">
            <h5 class="font-weight-bold">Data Pemohon</h5><hr>
            <div class="form-row">
                <div class="form-group col-md-6"><label>Nama Lengkap</label><input name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap', $customer->nama_lengkap) }}" required></div>
                <div class="form-group col-md-6"><label>NIK</label><input name="nik" class="form-control" value="{{ old('nik', $customer->nik) }}" required></div>
                <div class="form-group col-md-4"><label>Jenis Kelamin</label><select name="jenis_kelamin" class="form-control"><option value="Laki-laki" @selected($customer->jenis_kelamin === 'Laki-laki')>Laki-laki</option><option value="Perempuan" @selected($customer->jenis_kelamin === 'Perempuan')>Perempuan</option></select></div>
                <div class="form-group col-md-4"><label>Tempat Lahir</label><input name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir', $customer->tempat_lahir) }}" required></div>
                <div class="form-group col-md-4"><label>Tanggal Lahir</label><input name="tgl_lahir" type="date" class="form-control" value="{{ old('tgl_lahir', $customer->tgl_lahir) }}" required></div>
                <div class="form-group col-md-6"><label>No. Telepon</label><input name="no_telp" class="form-control" value="{{ old('no_telp', $customer->no_telp) }}" required></div>
                <div class="form-group col-md-6"><label>Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $customer->email) }}"></div>
                <div class="form-group col-md-6"><label>Alamat KTP</label><textarea name="alamat_ktp" class="form-control" required>{{ old('alamat_ktp', $customer->alamat_ktp) }}</textarea></div>
                <div class="form-group col-md-6"><label>Alamat Domisili</label><textarea name="alamat_domisili" class="form-control" required>{{ old('alamat_domisili', $customer->alamat_domisili) }}</textarea></div>
                <div class="form-group col-md-4"><label>Status Pernikahan</label><input name="status_pernikahan" class="form-control" value="{{ old('status_pernikahan', $customer->status_pernikahan) }}"></div>
                <div class="form-group col-md-4"><label>Nama Pasangan</label><input name="nama_p" class="form-control" value="{{ old('nama_p', $customer->nama_p) }}"></div>
                <div class="form-group col-md-4"><label>NIK Pasangan</label><input name="nik_p" class="form-control" value="{{ old('nik_p', $customer->nik_p) }}"></div>
                <div class="form-group col-md-4"><label>NPWP</label><input name="npwp" class="form-control" value="{{ old('npwp', $customer->npwp) }}"></div>
                <div class="form-group col-md-4"><label>No. BPJS</label><input name="no_bpjs_kes" class="form-control" value="{{ old('no_bpjs_kes', $customer->no_bpjs_kes) }}"></div>
                <div class="form-group col-md-4"><label>Pekerjaan</label><input class="form-control" value="{{ $customer->pekerjaan }}" readonly></div>
            </div>
            <h5 class="font-weight-bold mt-3">Data Pembelian</h5><hr>
            <div class="form-row">
                <div class="form-group col-md-4"><label>Marketing</label><select name="id_marketing" class="form-control">@foreach($marketingList as $m)<option value="{{ $m->id }}" @selected($customer->id_marketing == $m->id)>{{ $m->nama_marketing }}</option>@endforeach</select></div>
                <div class="form-group col-md-4"><label>Sumber Prospek</label><select name="sumber_prospek" class="form-control">@foreach(['Iklan Kantor','Market Place FB','Freelance','Kanvasing','Sosmed Pribadi','Sosmed Kantor','Referensi','WIC'] as $source)<option value="{{ $source }}" @selected($customer->sumber_prospek === $source)>{{ $source }}</option>@endforeach</select></div>
                <div class="form-group col-md-4"><label>Jenis Perumahan</label><select name="jenis_perumahan" class="form-control"><option value="Subsidi" @selected($customer->jenis_perumahan === 'Subsidi')>Subsidi</option><option value="Komersil" @selected($customer->jenis_perumahan === 'Komersil')>Komersil</option></select></div>
                <div class="form-group col-md-4"><label>Jenis Pembelian</label><select name="jenis_pembelian" class="form-control"><option value="Pembelian Cash" @selected($customer->jenis_pembelian === 'Pembelian Cash')>Pembelian Cash</option><option value="Cash Bertahap" @selected($customer->jenis_pembelian === 'Cash Bertahap')>Cash Bertahap</option><option value="KPR" @selected($customer->jenis_pembelian === 'KPR')>KPR</option></select></div>
                <div class="form-group col-md-4"><label>Diskon</label><input name="diskon" class="form-control" value="{{ old('diskon', $customer->diskon) }}"></div>
            </div>
            </div>
            <div class="col-lg-6 border-left pl-lg-4">
            <h5 class="font-weight-bold mt-3">Lampiran</h5><hr><div class="form-row">
                @foreach(['foto_ktp'=>'Foto KTP','foto_npwp'=>'Foto NPWP','foto_kk'=>'Foto KK','foto_bpjs'=>'Foto BPJS','foto_ktp_p'=>'Foto KTP Pasangan','file_bukti'=>'Bukti Transfer','foto_pemohon'=>'Foto Pemohon'] as $field => $label)
                    <div class="form-group col-md-6">
                        <label>{{ $label }}</label>
                        <div class="attachment-preview mb-2">
                            @if(isset($lampiran[$label]))
                                @if (Str::endsWith(strtolower($lampiran[$label]), ['.jpg', '.jpeg', '.png', '.webp']))
                                    <img src="{{ asset('assets/customer/'.$lampiran[$label]) }}" class="img-thumbnail attachment-image" data-title="{{ $label }}" alt="{{ $label }}" style="width:100%; height:150px; object-fit:cover; cursor:zoom-in;">
                                @else
                                    <a href="{{ asset('assets/customer/'.$lampiran[$label]) }}" target="_blank" class="btn btn-sm btn-secondary">Lihat PDF</a>
                                @endif
                            @else
                                <span class="text-muted small">Belum ada lampiran</span>
                            @endif
                        </div>
                        <input type="file" name="{{ $field }}" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                @endforeach
            </div>
            </div>
            </div>
            <h5 class="font-weight-bold mt-3">Lanjutkan Proses</h5><hr><div class="form-row">
                <div class="form-group col-md-4"><label>Tujuan Proses</label><select name="proses" id="proses" class="form-control"><option value="admin" @selected(old('proses', 'admin') === 'admin')>Proses Admin</option><option value="bank" @selected(old('proses') === 'bank')>Proses Bank</option></select></div>
                <div class="form-group col-md-4" id="bank-kpr-wrap" style="display:none"><label>Bank KPR</label><select name="id_bank_kpr" id="id_bank_kpr" class="form-control"><option value=""></option>@foreach($bankKPRList as $bank)<option value="{{ $bank->id }}" @selected((string) old('id_bank_kpr') === (string) $bank->id)>{{ $bank->nama }}</option>@endforeach</select>@error('id_bank_kpr')<small class="text-danger d-block">{{ $message }}</small>@enderror</div>
            </div>
            <a href="{{ route('proses-admin.index') }}" class="btn btn-secondary">Kembali</a><button class="btn btn-primary">Simpan</button>
        </form>
    </div></div></div></section>
</div>
<div class="modal fade" id="modalLampiran" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document"><div class="modal-content">
        <div class="modal-header bg-indigo text-white"><h5 class="modal-title" id="modalLampiranTitle">Lampiran</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body text-center bg-light"><img id="modalLampiranImage" class="img-fluid" style="max-height:75vh"></div>
    </div></div>
</div>
@endsection
@push('scripts')
<style>
    .booking-data .form-row { display: block; }
    .booking-data .form-group {
        display: grid;
        grid-template-columns: 145px minmax(0, 1fr);
        gap: 12px;
        align-items: center;
        margin: 0 0 12px;
    }
    .booking-data .form-group[class*="col-"] { width: 100%; max-width: 100%; flex: none; }
    .booking-data .form-group label { margin: 0; font-weight: 600; }
    .booking-data .form-group textarea { min-height: 72px; }
    .attachment-preview {
        height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: .25rem;
        overflow: hidden;
    }
    @media (max-width: 991.98px) {
        .col-lg-6.border-left { border-left: 0 !important; border-top: 1px solid #dee2e6; margin-top: 1rem; padding-top: 1rem; }
    }
    @media (max-width: 575.98px) {
        .booking-data .form-group { grid-template-columns: 1fr; gap: 4px; }
    }
</style>
<script>
$('#proses').on('change', function(){
    const isBank = this.value === 'bank';
    $('#bank-kpr-wrap').toggle(isBank);
    $('#id_bank_kpr').prop('required', isBank);
}).trigger('change');
$(document).on('click', '.attachment-image', function() {
    $('#modalLampiranImage').attr('src', $(this).attr('src'));
    $('#modalLampiranTitle').text($(this).data('title'));
    $('#modalLampiran').modal('show');
});
</script>
@endpush
