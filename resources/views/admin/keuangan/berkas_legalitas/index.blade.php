@extends('admin.layout_admin')

@section('content')
    <div class="content-wrapper">
        <section class="content-header"><div class="container-fluid"></div></section>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="font-weight-bold text-lg mb-0">Arsip Berkas Legalitas</h3>
                        @if ($permissions['tambah'])
                            <button class="btn btn-primary btn-sm" id="addButton"><i class="fas fa-plus"></i> Tambah Berkas</button>
                        @endif
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped w-100" id="dataTable">
                            <thead><tr><th width="35">No</th><th>Jenis</th><th>Keterangan</th><th width="180" class="text-center">Action</th></tr></thead>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="modalForm" tabindex="-1" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-indigo"><h5 class="modal-title text-white font-weight-bold" id="modalFormLabel">Tambah Berkas Legalitas</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
            <form id="formData" enctype="multipart/form-data">@csrf
                <input type="hidden" id="primaryId">
                <div class="modal-body">
                    <div class="form-group row"><label class="col-sm-3 col-form-label">Jenis Berkas</label><div class="col-sm-9"><input type="text" class="form-control" name="jenis" id="jenis" placeholder="Contoh: Sertifikat, IMB, PBG" required></div></div>
                    <div class="form-group row"><label class="col-sm-3 col-form-label">Keterangan</label><div class="col-sm-9"><textarea class="form-control" name="keterangan" id="keterangan" rows="3" placeholder="Keterangan berkas"></textarea></div></div>
                    <div class="form-group row mb-0"><label class="col-sm-3 col-form-label">Lampiran</label><div class="col-sm-9">
                        <input type="file" class="mb-2" name="lampiran" id="lampiran" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="d-block text-muted mb-2">Format JPG, JPEG, PNG, atau PDF. Maksimal 5 MB.</small>
                        <div id="previewLampiran" class="img-thumbnail d-flex align-items-center justify-content-center" style="width: 100%; height: 220px; overflow: hidden;"><span class="text-muted">Belum ada lampiran</span></div>
                    </div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="modalDetail" tabindex="-1">
        <div class="modal-dialog modal-xl"><div class="modal-content">
            <div class="modal-header bg-indigo"><h5 class="modal-title text-white font-weight-bold">Detail Berkas Legalitas</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="form-group row"><label class="col-sm-2 col-form-label">Jenis Berkas</label><div class="col-sm-10"><input class="form-control" id="detailJenis" readonly></div></div>
                <div class="form-group row"><label class="col-sm-2 col-form-label">Keterangan</label><div class="col-sm-10"><textarea class="form-control" id="detailKeterangan" rows="3" readonly></textarea></div></div>
                <div class="form-group row mb-0"><label class="col-sm-2 col-form-label">Lampiran</label><div class="col-sm-10"><div id="detailLampiran" class="border rounded d-flex align-items-center justify-content-center" style="min-height: 500px; overflow: hidden;"><span class="text-muted">Tidak ada lampiran</span></div></div></div>
            </div>
        </div></div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#dataTable').DataTable({
        processing: false, serverSide: false, ordering: false, responsive: true,
        ajax: "{{ route('berkas-legalitas.index') }}",
        columns: [
            { data: 'DT_RowIndex', searchable: false, className: 'text-center' },
            { data: 'jenis' },
            { data: 'keterangan', defaultContent: '-' },
            { data: 'action', searchable: false, className: 'text-center' }
        ]
    });

    function resetForm() {
        $('#formData')[0].reset(); $('#primaryId').val('');
        $('#previewLampiran').html('<span class="text-muted">Belum ada lampiran</span>');
        $('#lampiran').prop('required', true);
    }

    function preview(url, filename, target, height) {
        if (!filename) { $(target).html('<span class="text-muted">Tidak ada lampiran</span>'); return; }
        if (filename.split('.').pop().toLowerCase() === 'pdf') {
            $(target).html('<iframe src="' + url + '" title="Lampiran PDF" style="width:100%; height:' + height + 'px; border:0;"></iframe>');
        } else {
            $(target).html('<img src="' + url + '" alt="Lampiran" style="max-width:100%; max-height:' + height + 'px; object-fit:contain;">');
        }
    }

    $('#lampiran').on('change', function () {
        const file = this.files[0];
        if (!file) return;
        preview(URL.createObjectURL(file), file.name, '#previewLampiran', 210);
    });

    $('#addButton').on('click', function () { resetForm(); $('#modalFormLabel').text('Tambah Berkas Legalitas'); $('#modalForm').modal('show'); });

    $(document).on('click', '.edit-button', function () {
        resetForm();
        $.get($(this).data('url'), function (response) {
            const data = response.data;
            $('#primaryId').val(data.id); $('#jenis').val(data.jenis); $('#keterangan').val(data.keterangan);
            $('#lampiran').prop('required', false);
            preview('/assets/keuangan/berkas_legalitas/' + data.lampiran, data.lampiran, '#previewLampiran', 210);
            $('#modalFormLabel').text('Edit Berkas Legalitas'); $('#modalForm').modal('show');
        });
    });

    $(document).on('click', '.detail-button', function () {
        $.get($(this).data('url'), function (response) {
            const data = response.data;
            $('#detailJenis').val(data.jenis); $('#detailKeterangan').val(data.keterangan || '-');
            preview('/assets/keuangan/berkas_legalitas/' + data.lampiran, data.lampiran, '#detailLampiran', 500);
            $('#modalDetail').modal('show');
        });
    });

    $('#formData').on('submit', function (event) {
        event.preventDefault();
        const id = $('#primaryId').val(); const formData = new FormData(this);
        if (id) formData.append('_method', 'PUT');
        $('#submitBtn').prop('disabled', true);
        $.ajax({ url: id ? "{{ url('admin/keuangan/berkas-legalitas') }}/" + id : "{{ route('berkas-legalitas.store') }}", type: 'POST', data: formData, processData: false, contentType: false,
            success: function () { $('#modalForm').modal('hide'); table.ajax.reload(null, false); toastr.success(id ? 'Berkas berhasil diperbarui.' : 'Berkas berhasil ditambahkan.'); },
            error: function (xhr) { toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Data gagal disimpan.'); },
            complete: function () { $('#submitBtn').prop('disabled', false); }
        });
    });

    $(document).on('submit', '.delete-button', function (event) {
        event.preventDefault(); const form = this;
        Swal.fire({ title: 'Hapus berkas?', text: 'Lampiran juga akan dihapus.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal' }).then(function (result) {
            if (!result.isConfirmed) return;
            $.post($(form).attr('action'), $(form).serialize(), function () { table.ajax.reload(null, false); toastr.success('Berkas berhasil dihapus.'); });
        });
    });
});
</script>
@endpush
