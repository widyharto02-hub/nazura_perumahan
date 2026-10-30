@extends('admin.layout_admin')
@section('content')
    <style>
        #modalForm .utility-body { background: #f4f6fa; }
        #modalForm .utility-section { background: #fff; border: 1px solid #e3e8ef; border-radius: 12px; padding: 20px; }
        #modalForm .attachment-tile { height: 140px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        #modalForm .attachment-tile button { width: 100%; height: 100%; border: 0; background: transparent; padding: 8px; }
        #modalForm .attachment-tile img { width: 100%; height: 100%; object-fit: contain; }
        #modalForm .modal-footer { background: #fff; }
        #attachmentPreview { position: fixed; inset: 0; z-index: 1080; background: rgba(15,23,42,.9); padding: 24px; }
        #attachmentPreview .preview-content { height: calc(100% - 56px); display: flex; justify-content: center; align-items: center; }
        #attachmentPreview img { max-width: 100%; max-height: 100%; object-fit: contain; }
        #attachmentPreview iframe { width: 100%; height: 100%; border: 0; background: white; }
    </style>
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header p-3">
                                <div class="d-flex align-content-center justify-content-between">
                                    <h3 class="font-weight-bold text-xl">Data Rekening Listrik & Air</h3>
                                    <div class="d-flex align-items-center">
                                        @if ($permissions['tambah'])
                                            <button class="btn btn-primary btn-sm" data-toggle="modal"
                                                data-target="#modalForm"><i class="fas fa-plus"></i>
                                                Tambah Listrik & Air</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="table" class="table table-bordered table-striped data-table">
                                    <thead>
                                        <tr>
                                            <th width="30px">No</th>
                                            <th>Lokasi Rumah</th>
                                            <th>No. Rek. Listrik</th>
                                            <th>No. Rek. Air</th>
                                            <th class="text-center" width="100px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="modalFormLabel" aria-hidden="true"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-indigo">
                    <h5 class="modal-title text-white font-weight-bold" id="modalFormLabel"></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formData" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="primary_id" name="primary_id">
                    <div class="modal-body utility-body">
                        <div class="alert alert-light border mb-4"><i class="fas fa-info-circle text-primary mr-2"></i>Isi listrik atau air terlebih dahulu, lalu lengkapi melalui Edit. Lampiran bersifat opsional.</div>
                        <section class="utility-section mb-4">
                            <h6 class="font-weight-bold mb-3"><i class="fas fa-map-marker-alt text-primary mr-2"></i>Lokasi unit</h6>
                            <div class="row">
                                <div class="form-group col-md-7 mb-md-0">
                                    <label for="id_lokasi">Lokasi perumahan</label>
                                    <select name="id_lokasi" id="id_lokasi" class="form-control select-lokasi" style="width:100%">
                                        <option value=""></option>
                                        @foreach ($lokasi as $l)
                                            <option value="{{ $l->id }}">{{ $l->nama_kavling }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-5 mb-0">
                                    <label for="id_kavling">Blok / Unit</label>
                                    <select name="id_kavling" id="id_kavling" class="form-control select-kavling" style="width:100%"></select>
                                    <small id="unitHelp" class="text-muted d-block mt-2">Hanya unit yang belum memiliki data ditampilkan.</small>
                                </div>
                            </div>
                        </section>
                        <div class="row">
                            <div class="col-lg-6 mb-3"><section class="utility-section h-100">
                                <h6 class="font-weight-bold mb-3"><i class="fas fa-bolt text-primary mr-2"></i>Listrik</h6>
                                <div class="form-group"><label for="norek_listrik">Nomor rekening listrik</label>
                                    <input type="text" maxlength="35" id="norek_listrik" name="norek_listrik" class="form-control" placeholder="Masukkan nomor rekening">
                                </div>
                                <div class="row">
                                    <div class="col-sm-6 mb-3">
                                        <label for="foto_listrik" class="small font-weight-bold">Meteran listrik 1</label>
                                        <div class="attachment-tile" data-field="foto_listrik"><span class="text-muted small">Belum ada lampiran</span></div>
                                        <input type="hidden" name="remove_foto_listrik" id="remove_foto_listrik" value="0">
                                        <input type="file" class="sr-only utility-file" id="foto_listrik" name="foto_listrik" accept=".jpg,.jpeg,.png,.pdf">
                                        <div class="d-flex mt-2">
                                            <label for="foto_listrik" class="btn btn-sm btn-outline-primary mb-0 mr-1 flex-fill attachment-upload">Unggah / Ganti</label>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-attachment" data-field="foto_listrik" aria-label="Hapus lampiran meteran listrik 1" disabled><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label for="foto_listrik_2" class="small font-weight-bold">Meteran listrik 2</label>
                                        <div class="attachment-tile" data-field="foto_listrik_2"><span class="text-muted small">Belum ada lampiran</span></div>
                                        <input type="hidden" name="remove_foto_listrik_2" id="remove_foto_listrik_2" value="0">
                                        <input type="file" class="sr-only utility-file" id="foto_listrik_2" name="foto_listrik_2" accept=".jpg,.jpeg,.png,.pdf">
                                        <div class="d-flex mt-2">
                                            <label for="foto_listrik_2" class="btn btn-sm btn-outline-primary mb-0 mr-1 flex-fill attachment-upload">Unggah / Ganti</label>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-attachment" data-field="foto_listrik_2" aria-label="Hapus lampiran meteran listrik 2" disabled><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">JPG, PNG, atau PDF. Maksimal 2 MB per lampiran.<br>Klik lampiran untuk melihat pratinjau.</small>
                            </section></div>
                            <div class="col-lg-6 mb-3"><section class="utility-section h-100">
                                <h6 class="font-weight-bold mb-3"><i class="fas fa-tint text-primary mr-2"></i>Air</h6>
                                <div class="form-group"><label for="norek_air">Nomor rekening air</label>
                                    <input type="text" maxlength="35" id="norek_air" name="norek_air" class="form-control" placeholder="Masukkan nomor rekening">
                                </div>
                                <div class="row">
                                    <div class="col-sm-6 mb-3">
                                        <label for="foto_air" class="small font-weight-bold">Meteran air 1</label>
                                        <div class="attachment-tile" data-field="foto_air"><span class="text-muted small">Belum ada lampiran</span></div>
                                        <input type="hidden" name="remove_foto_air" id="remove_foto_air" value="0">
                                        <input type="file" class="sr-only utility-file" id="foto_air" name="foto_air" accept=".jpg,.jpeg,.png,.pdf">
                                        <div class="d-flex mt-2">
                                            <label for="foto_air" class="btn btn-sm btn-outline-primary mb-0 mr-1 flex-fill attachment-upload">Unggah / Ganti</label>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-attachment" data-field="foto_air" aria-label="Hapus lampiran meteran air 1" disabled><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label for="foto_air_2" class="small font-weight-bold">Meteran air 2</label>
                                        <div class="attachment-tile" data-field="foto_air_2"><span class="text-muted small">Belum ada lampiran</span></div>
                                        <input type="hidden" name="remove_foto_air_2" id="remove_foto_air_2" value="0">
                                        <input type="file" class="sr-only utility-file" id="foto_air_2" name="foto_air_2" accept=".jpg,.jpeg,.png,.pdf">
                                        <div class="d-flex mt-2">
                                            <label for="foto_air_2" class="btn btn-sm btn-outline-primary mb-0 mr-1 flex-fill attachment-upload">Unggah / Ganti</label>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-attachment" data-field="foto_air_2" aria-label="Hapus lampiran meteran air 2" disabled><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">JPG, PNG, atau PDF. Maksimal 2 MB per lampiran.<br>Klik lampiran untuk melihat pratinjau.</small>
                            </section></div>
                        </div>
                        <small class="text-muted">Penghapusan dan penggantian lampiran diterapkan setelah Anda menekan Simpan.</small>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary " id="submitBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            <span class="button-text">Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="attachmentPreview" class="d-none" role="dialog" aria-modal="true" aria-label="Pratinjau lampiran">
        <div class="d-flex align-items-center justify-content-between mb-3 text-white"><strong>Pratinjau lampiran</strong><button type="button" id="closeAttachmentPreview" class="btn btn-light btn-sm">Tutup <i class="fas fa-times ml-1"></i></button></div>
        <div class="preview-content"></div>
    </div>
@endsection
@push('scripts')
    <script>
        const attachments = {
            foto_listrik: 'listrik_1', foto_listrik_2: 'listrik_2',
            foto_air: 'air_1', foto_air_2: 'air_2'
        };
        const attachmentUrls = {};
        const attachmentBase = @json(asset('assets/legal/listrik_air'));
        function renderAttachment(field, url = '', pdf = false) {
            const tile = $('.attachment-tile[data-field="' + field + '"]').empty();
            const old = attachmentUrls[field];
            if (old && old.url.startsWith('blob:')) URL.revokeObjectURL(old.url);
            attachmentUrls[field] = {url, pdf};
            $('.remove-attachment[data-field="' + field + '"]').prop('disabled', !url);
            if (!url) return tile.append($('<span class="text-muted small">').text('Belum ada lampiran'));
            const button = $('<button type="button" class="open-attachment" aria-label="Lihat pratinjau lampiran">').attr('data-field', field);
            button.append(pdf ? $('<span class="text-primary"><i class="fas fa-file-pdf fa-2x d-block mb-2"></i>Lihat PDF</span>') : $('<img alt="Lampiran meteran">').attr('src', url));
            tile.append(button);
        }
        $('.utility-file').on('change', function() {
            const file = this.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024 || !/\.(jpe?g|png|pdf)$/i.test(file.name)) {
                this.value = '';
                toastr.error('Gunakan JPG, PNG, atau PDF maksimal 2 MB.');
                return;
            }
            $('#remove_' + this.id).val('0');
            renderAttachment(this.id, URL.createObjectURL(file), /\.pdf$/i.test(file.name));
        });
        $('.remove-attachment').on('click', function() {
            const field = $(this).data('field');
            $('#' + field).val('');
            $('#remove_' + field).val('1');
            renderAttachment(field);
        });
        let previewTrigger;
        $('#attachmentPreview').appendTo('#modalForm');
        $('.attachment-tile').on('click', '.open-attachment', function() {
            const item = attachmentUrls[$(this).data('field')];
            if (!item || !item.url) return;
            previewTrigger = this;
            const content = $('#attachmentPreview .preview-content').empty();
            content.append(item.pdf ? $('<iframe title="Pratinjau PDF">').attr('src', item.url) : $('<img alt="Pratinjau lampiran meteran">').attr('src', item.url));
            $('#attachmentPreview').removeClass('d-none');
            $('#closeAttachmentPreview').trigger('focus');
        });
        function closeAttachmentPreview() {
            $('#attachmentPreview').addClass('d-none').find('.preview-content').empty();
            if (previewTrigger) previewTrigger.focus();
        }
        $('#closeAttachmentPreview').on('click', closeAttachmentPreview);
        $(document).on('keydown', function(event) {
            if ($('#attachmentPreview').hasClass('d-none')) return;
            if (event.key === 'Escape') { event.preventDefault(); event.stopImmediatePropagation(); closeAttachmentPreview(); }
            if (event.key === 'Tab') { event.preventDefault(); $('#closeAttachmentPreview').trigger('focus'); }
        });

        $(document).ready(function() {
            $('.select-lokasi').select2({
                dropdownParent: $('#modalForm'),
                theme: "bootstrap4",
                placeholder: "Pilih Lokasi",
            });
            $('.select-kavling').select2({
                dropdownParent: $('#modalForm'),
                theme: "bootstrap4",
                placeholder: "Pilih Blok / Unit",
            });
        });

        $(document).on('click', '[data-target="#modalForm"]', function() {
            $('#modalFormLabel').text('Tambah Listrik dan Air');
        });

        const routeGetKavling = "{{ route('listrik-air.units') }}";

        let kavlingRequest;
        $('#id_lokasi').on('change', function(event, selectedKavling) {
            if (kavlingRequest) kavlingRequest.abort();
            let idLokasi = $(this).val();
            $('#id_kavling').prop('disabled', false).empty().append(new Option('', '')).trigger('change');

            if (idLokasi) {
                $('#id_kavling').prop('disabled', true);
                $('#unitHelp').text('Memuat unit...');
                kavlingRequest = $.get(routeGetKavling, {id_lokasi: idLokasi, except_id: $('#primary_id').val()}, function(data) {
                    data.forEach(function(item) {
                        $('#id_kavling').append(new Option(item.kode_kavling, item.id));
                    });
                    $('#id_kavling').prop('disabled', false).val(selectedKavling || '').trigger('change');
                    $('#unitHelp').text(data.length ? 'Unit yang sudah terdaftar tidak dapat ditambahkan lagi.' : 'Tidak ada unit tersedia di lokasi ini.');
                }).fail(function(xhr, status) {
                    if (status !== 'abort') $('#unitHelp').text('Gagal memuat unit. Pilih ulang lokasi untuk mencoba lagi.');
                });
            }
        });

        $(function() {
            var permissions = @json($permissions);
            var showActionColumn = (permissions['edit'] == 1 || permissions['hapus'] == 1);

            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: false,
                ordering: false,
                responsive: true,
                ajax: "{{ route('listrik-air.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'lokasi_rumah',
                        name: 'lokasi_rumah',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'norek_listrik',
                        name: 'norek_listrik',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'norek_air',
                        name: 'norek_air',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        visible: showActionColumn,
                        className: 'text-center'
                    }
                ],
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }, ]
            });
        });

        $(document).on('click', '.edit-button', function() {
            var url = $(this).data('url');

            $.get(url, function(response) {
                if (response.status === 'success') {
                    $('#modalFormLabel').text('Edit Listrik dan Air');
                    $('#primary_id').val(response.data.id);
                    $('#id_lokasi').val(response.data.id_lokasi)
                        .trigger('change', [response.data.id_kavling]);

                    $('#norek_listrik').val(response.data.norek_listrik);
                    $('#norek_air').val(response.data.norek_air);

                    Object.entries(attachments).forEach(([field, folder]) => {
                        const name = response.data[field];
                        renderAttachment(field, name ? attachmentBase + '/' + folder + '/' + encodeURIComponent(name) : '', /\.pdf$/i.test(name || ''));
                    });

                    $('#modalForm').modal('show');
                }
            });
        });

        $('#modalForm').on('hidden.bs.modal', function() {
            $('#formData')[0].reset();
            $('#primary_id').val('');
            $('.select-lokasi').val('').trigger('change');
            $('.select-kavling').val('').trigger('change');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.addClass('d-none');
            btnText.text('Simpan');
            submitBtn.prop('disabled', false);

            closeAttachmentPreview();
            Object.keys(attachments).forEach(field => renderAttachment(field));
            $('#unitHelp').text('Hanya unit yang belum memiliki data ditampilkan.');
        });

        // Simpan / Update data
        $('#formData').on('submit', function(e) {
            e.preventDefault();

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            let id = $('#primary_id').val();
            let url = id ? '{{ route('listrik-air.update', ['listrik_air' => ':id']) }}'.replace(':id', id) :
                '{{ route('listrik-air.store') }}';
            let method = id ? 'PUT' : 'POST';

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let formData = new FormData(this);
            formData.append('_method', method);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    $('#modalForm').modal('hide');
                    audio.play();
                    toastr.success("Data telah disimpan!", "BERHASIL", {
                        progressBar: true,
                        timeOut: 3500,
                        positionClass: "toast-bottom-right",
                    });
                    $('.data-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    spinner.addClass('d-none');
                    btnText.text('Simpan');
                    submitBtn.prop('disabled', false);
                    if (xhr.status !== 422) toastr.error('Data gagal disimpan. Silakan coba lagi.');
                    if (xhr.status === 422) {
                        audio.play();
                        toastr.error("Ada inputan yang salah!", "GAGAL!", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right",
                        });

                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, val) {
                            let input = $('#' + key);
                            input.addClass('is-invalid');
                            input.parent().find('.invalid-feedback').remove();
                            input.parent().append(
                                '<span class="invalid-feedback" role="alert"><strong>' +
                                val[0] + '</strong></span>'
                            );
                        });
                        spinner.addClass('d-none');
                        btnText.text('Simpan');
                        submitBtn.prop('disabled', false);
                    }
                }
            });
        });

        // Hapus data
        $(document).on('click', '.delete-button', function(e) {
            e.preventDefault();

            const form = $(this).closest('form');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<span class="swal-btn-text">Ya, Hapus</span>',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: false,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger mx-2',
                    cancelButton: 'btn btn-secondary'
                },
                preConfirm: () => {
                    return new Promise((resolve) => {
                        const confirmBtn = Swal.getConfirmButton();
                        const btnText = confirmBtn.querySelector('.swal-btn-text');

                        btnText.innerHTML =
                            `<span class="spinner-border spinner-border-sm mx-2" role="status" aria-hidden="true"></span> Menghapus...`;
                        confirmBtn.disabled = true;

                        $.ajax({
                            url: form.attr('action'),
                            method: 'POST',
                            data: form.serialize(),
                            success: function() {
                                audio.play();
                                toastr.success("Data telah dihapus!", "BERHASIL", {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: "toast-bottom-right"
                                });

                                $('.data-table').DataTable().ajax.reload(null,
                                    false);
                                Swal.close();
                            },
                            error: function() {
                                audio.play();
                                toastr.error("Gagal menghapus data.", "GAGAL!", {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: "toast-bottom-right"
                                });

                                btnText.innerHTML = `Ya, Hapus`;
                                confirmBtn.disabled = false;
                            }
                        });
                    });
                }
            });
        });
    </script>
@endpush
