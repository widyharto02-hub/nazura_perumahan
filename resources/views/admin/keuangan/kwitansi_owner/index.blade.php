@extends('admin.layout_admin')

@section('content')
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"></div></section>
    <section class="content"><div class="container-fluid">
        <div class="card">
            <div class="card-header bg-indigo"><h3 class="card-title font-weight-bold">Kwitansi Menunggu Persetujuan Owner</h3></div>
            <div class="card-body table-responsive">
                @if (! $isOwner)
                    <div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i> Anda dapat melihat daftar ini, tetapi persetujuan hanya dapat dilakukan oleh Owner.</div>
                @endif
                <table class="table table-bordered table-striped" id="tableKwitansiOwner">
                    <thead><tr><th>No</th><th>Tanggal</th><th>No. Kwitansi</th><th>Customer</th><th>Kategori</th><th>Nominal</th><th>Aksi</th></tr></thead>
                </table>
            </div>
        </div>
    </div></section>
</div>
<div class="modal fade" id="modalDetailOwner" tabindex="-1" role="dialog" aria-labelledby="modalDetailOwnerLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-indigo">
                <h5 class="modal-title" id="modalDetailOwnerLabel">Detail Pembayaran Kwitansi</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="detailOwnerContent" aria-live="polite"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    let detailRequest = null;
    $(document).on('click', '.btn-detail-owner', function () {
        if (detailRequest) detailRequest.abort();
        $('#detailOwnerContent').html('<div class="text-center text-muted py-4">Memuat detail pembayaran...</div>');
        $('#modalDetailOwner').modal('show');
        detailRequest = $.get($(this).attr('data-url')).done(function (html) {
            $('#detailOwnerContent').html(html);
        }).fail(function (xhr, status) {
            if (status === 'abort') return;
            $('#detailOwnerContent').html('<div class="alert alert-danger mb-0">Detail pembayaran gagal dimuat. Tutup modal dan coba lagi.</div>');
        });
    });
    $('#modalDetailOwner').on('hidden.bs.modal', function () {
        if (detailRequest) detailRequest.abort();
        detailRequest = null;
        $('#detailOwnerContent').empty();
    });
    const table = $('#tableKwitansiOwner').DataTable({
        processing: true, serverSide: true, ajax: "{{ route('kwitansi-owner.index') }}",
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false}, {data: 'tanggal'}, {data: 'no_kwitansi'},
            {data: 'customer'}, {data: 'kategori'}, {data: 'nominal', className: 'text-right'},
            {data: 'action', orderable: false, searchable: false, className: 'text-center'}
        ]
    });
    $(document).on('submit', '.form-setujui-owner', function (event) {
        event.preventDefault(); const form = $(this);
        Swal.fire({title: 'Setujui kwitansi?', text: 'Tanda tangan owner akan tampil saat kwitansi dicetak.', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Setujui'}).then(function (result) {
            if (!result.isConfirmed) return;
            $.post(form.attr('action'), form.serialize()).done(function (response) {
                toastr.success(response.message); table.ajax.reload(null, false);
            }).fail(function (xhr) { toastr.error(xhr.responseJSON?.message || 'Kwitansi gagal disetujui.'); });
        });
    });
});
</script>
@endpush
