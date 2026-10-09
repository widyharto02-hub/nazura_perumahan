@extends('admin.layout_admin')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid"></div>
        </section>
        <section class="content">
            <div class="container-fluid">
                @if ($bolehLihatPersetujuan && $kwitansiMenungguOwner > 0)
                    <a href="{{ route('kwitansi-owner.index') }}"
                        class="alert alert-warning d-flex align-items-center justify-content-between text-dark">
                        <span><i class="fas fa-exclamation-triangle mr-2"></i><strong>{{ $kwitansiMenungguOwner }}
                                kwitansi</strong> masih menunggu persetujuan owner.</span>
                        <span class="font-weight-bold">Lihat daftar <i class="fas fa-arrow-right ml-1"></i></span>
                    </a>
                @endif
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-weight-bold text-lg mb-0">Dashboard Keuangan</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <div class="form-group mb-md-0"><label>Periode Mulai</label><input id="tanggal_mulai"
                                        class="form-control" type="date" value="{{ $tanggalMulai }}"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-md-0"><label>Periode Selesai</label><input id="tanggal_selesai"
                                        class="form-control" type="date" value="{{ $tanggalSelesai }}"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-md-0"><label>Perumahan / Kantor</label><select id="id_lokasi"
                                        class="form-control select-lokasi">
                                        <option value="">Semua Perumahan / Kantor</option>
                                        <option value="0" @selected((string) $idLokasi === '0')>Office Nazura Group</option>
                                        @foreach ($lokasiList as $lokasi)
                                            <option value="{{ $lokasi->id }}" @selected((string) $idLokasi === (string) $lokasi->id)>
                                                {{ $lokasi->nama_kavling }}</option>
                                        @endforeach
                                    </select></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3 id="totalPemasukan">Rp 0</h3>
                                <p>Total Pemasukan</p>
                            </div>
                            <div class="icon"><i class="fas fa-arrow-circle-down"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3 id="totalPengeluaran">Rp 0</h3>
                                <p>Total Pengeluaran</p>
                            </div>
                            <div class="icon"><i class="fas fa-arrow-circle-up"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <div class="small-box bg-indigo">
                            <div class="inner">
                                <h3 id="selisihKas">Rp 0</h3>
                                <p>Selisih Kas</p>
                            </div>
                            <div class="icon"><i class="fas fa-wallet"></i></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="font-weight-bold text-lg mb-0">Ringkasan Keuangan per Perumahan / Kantor</h3>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Perumahan / Kantor</th>
                                    <th class="text-right">Pemasukan</th>
                                    <th class="text-right">Pengeluaran</th>
                                    <th class="text-right">Selisih</th>
                                </tr>
                            </thead>
                            <tbody id="ringkasanBody">
                                <tr>
                                    <td colspan="5" class="text-center">Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                        <small class="text-muted d-block mt-3">Klik nominal pemasukan atau pengeluaran untuk melihat
                            rinciannya.</small>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="modalDetail" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-indigo">
                    <h5 class="modal-title text-white font-weight-bold" id="detailTitle">Rincian Transaksi</h5><button
                        type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Keterangan</th>
                                <th class="text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody id="detailBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function formatRupiah(angka) {
            return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
        }

        function escapeHtml(teks) {
            return $('<div>').text(teks === undefined || teks === null ? '' : teks).html();
        }

        function muatRingkasan() {
            const params = {
                tanggal_mulai: $('#tanggal_mulai').val(),
                tanggal_selesai: $('#tanggal_selesai').val(),
                id_lokasi: $('#id_lokasi').val()
            };
            $('#totalPemasukan, #totalPengeluaran, #selisihKas').text('Memuat...');
            $('#ringkasanBody').html('<tr><td colspan="5" class="text-center">Memuat data...</td></tr>');
            $.get("{{ route('dashboard-keuangan.data') }}", params, function(response) {
                const pemasukan = Number(response.total_pemasukan || 0);
                const pengeluaran = Number(response.total_pengeluaran || 0);
                $('#totalPemasukan').text(formatRupiah(pemasukan));
                $('#totalPengeluaran').text(formatRupiah(pengeluaran));
                $('#selisihKas').text(formatRupiah(pemasukan - pengeluaran));
                if (!response.proyek.length) {
                    $('#ringkasanBody').html(
                        '<tr><td colspan="5" class="text-center text-muted">Belum ada transaksi pada periode ini.</td></tr>'
                        );
                    return;
                }
                $('#ringkasanBody').html(response.proyek.map(function(item, index) {
                    const selisih = Number(item.pemasukan) - Number(item.pengeluaran);
                    const kelas = selisih < 0 ? 'text-danger' : 'text-primary';
                    return '<tr><td>' + (index + 1) + '</td><td>' + escapeHtml(item.nama_proyek) +
                        '</td>' +
                        '<td class="text-right"><button class="btn btn-link p-0 detail-button text-success" data-tipe="pemasukan" data-id-lokasi="' +
                        item.id_lokasi + '">' + formatRupiah(item.pemasukan) + '</button></td>' +
                        '<td class="text-right"><button class="btn btn-link p-0 detail-button text-danger" data-tipe="pengeluaran" data-id-lokasi="' +
                        item.id_lokasi + '">' + formatRupiah(item.pengeluaran) + '</button></td>' +
                        '<td class="text-right font-weight-bold ' + kelas + '">' + formatRupiah(
                        selisih) + '</td></tr>';
                }).join(''));
            }).fail(function(xhr) {
                const pesan = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message :
                    'Gagal memuat ringkasan keuangan.';
                toastr.error(pesan, 'GAGAL', {
                    progressBar: true,
                    timeOut: 3500,
                    positionClass: 'toast-bottom-right'
                });
                $('#ringkasanBody').html(
                    '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>');
            });
        }
        $(document).on('click', '.detail-button', function() {
            const button = $(this),
                tipe = button.data('tipe');
            $('#detailTitle').text('Rincian ' + (tipe === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'));
            $('#detailBody').html('<tr><td colspan="4" class="text-center">Memuat data...</td></tr>');
            $('#modalDetail').modal('show');
            $.get("{{ route('dashboard-keuangan.detail') }}", {
                tipe: tipe,
                id_lokasi: button.data('id-lokasi'),
                tanggal_mulai: $('#tanggal_mulai').val(),
                tanggal_selesai: $('#tanggal_selesai').val()
            }, function(response) {
                if (!response.data.length) {
                    $('#detailBody').html(
                        '<tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>');
                    return;
                }
                $('#detailBody').html(response.data.map(function(item) {
                    return '<tr><td>' + item.tanggal + '</td><td>' + item.kategori +
                        '</td><td>' + item.keterangan + '</td><td class="text-right">Rp ' + item
                        .nominal + '</td></tr>';
                }).join(''));
            });
        });
        $(function() {
            $('.select-lokasi').select2({
                theme: 'bootstrap4',
                placeholder: 'Semua Perumahan / Kantor',
                allowClear: true
            });
            $('#tanggal_mulai, #tanggal_selesai').on('change', muatRingkasan);
            $('#id_lokasi').on('change', muatRingkasan);
            muatRingkasan();
        });
    </script>
@endpush
