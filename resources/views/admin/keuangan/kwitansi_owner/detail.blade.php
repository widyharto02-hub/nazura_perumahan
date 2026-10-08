<dl class="row mb-3">
    @foreach ([
        'No. Kwitansi' => $pemasukan->no_kwitansi,
        'Tanggal' => $pemasukan->tanggal ? \Carbon\Carbon::parse($pemasukan->tanggal)->translatedFormat('d F Y') : '-',
        'Customer' => $pemasukan->customer->nama_lengkap ?? '-',
        'Kode Customer' => $pemasukan->customer->kode_customer ?? '-',
        'Kategori' => $pemasukan->kategori->kategori ?? '-',
        'Keterangan Kategori' => $pemasukan->keterangan_kategori,
        'Nominal Pembayaran' => 'Rp ' . number_format($pemasukan->nominal, 0, ',', '.'),
        'Metode Pembayaran' => $pemasukan->metode->jenis_bayar ?? '-',
        'Bank / Kas' => $pemasukan->bank->nama ?? '-',
        'No. Rekening' => $pemasukan->bank->no_rek ?? '-',
        'Pemilik Rekening' => $pemasukan->bank->pemilik_rek ?? '-',
        'Keterangan' => $pemasukan->keterangan,
        'Status' => $pemasukan->disetujui_owner_pada ? 'Sudah disetujui owner' : 'Menunggu persetujuan owner',
    ] as $label => $value)
        <dt class="col-sm-4">{{ $label }}</dt>
        <dd class="col-sm-8" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $value ?: '-' }}</dd>
    @endforeach
</dl>
<h6 class="font-weight-bold">Lampiran Pembayaran</h6>
@if ($pemasukan->lampiran)
    @php
        $lampiranUrl = asset('assets/keuangan/pemasukan/' . rawurlencode($pemasukan->lampiran));
        $extension = strtolower(pathinfo($pemasukan->lampiran, PATHINFO_EXTENSION));
    @endphp
    <a href="{{ $lampiranUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm mb-3">Buka Lampiran</a>
    <div class="border rounded p-2 text-center">
        @if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']))
            <img src="{{ $lampiranUrl }}" alt="Lampiran pembayaran" class="img-fluid" style="max-height: 600px;">
        @elseif ($extension === 'pdf')
            <object data="{{ $lampiranUrl }}" type="application/pdf" width="100%" height="550">
                <p>Pratinjau PDF tidak tersedia. Klik Buka Lampiran untuk melihat file.</p>
            </object>
        @else
            <p class="text-muted mb-0">Klik Buka Lampiran untuk melihat file.</p>
        @endif
    </div>
@else
    <div class="alert alert-light border mb-0">Tidak ada lampiran pembayaran.</div>
@endif
