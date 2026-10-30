<form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center flex-wrap" style="gap: .5rem;">
    <label for="siteplan-location" class="mb-0">Lokasi</label>
    <select id="siteplan-location" name="id_lokasi" class="form-control form-control-sm"
        style="width: 240px; max-width: 100%;" onchange="this.form.submit()">
        @foreach ($lokasiOptions as $option)
            <option value="{{ $option->id }}" @selected($selectedLocation?->id === $option->id)>{{ $option->nama_kavling }}</option>
        @endforeach
    </select>
    <label for="siteplan-type" class="mb-0">Siteplan</label>
    <select id="siteplan-type" class="form-control form-control-sm"
        style="width: 220px; max-width: 100%;" onchange="window.location.href = this.value">
        @foreach (\App\Services\SiteplanSelection::navigation() as $siteplanMenu)
            <option value="{{ route($siteplanMenu->route_name, $selectedLocation ? ['id_lokasi' => $selectedLocation->id] : []) }}"
                @selected(request()->routeIs($siteplanMenu->route_name))>{{ $siteplanMenu->title }}</option>
        @endforeach
    </select>
    @if ($selectedLocation)
        <a href="{{ route($printRoute . '.cetak.pdf', $selectedLocation->id) }}" target="_blank" rel="noopener" class="btn btn-danger btn-sm">
            <i class="fas fa-file-pdf mr-1"></i> Cetak Denah PDF
        </a>
        <a href="{{ route($printRoute . '.cetak.jpg', $selectedLocation->id) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
            <i class="fas fa-file-image mr-1"></i> Download Denah JPG
        </a>
    @endif
</form>
