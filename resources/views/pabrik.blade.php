@extends('layoutsAdmin.app')

@section('page_title', 'Lokasi Pabrik')
@section('title', 'Lokasi Pabrik')
@section('subtitle', 'Tempat truk memuat barang. Dipakai untuk mendeteksi otomatis status "Sedang Muat di Pabrik".')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
@endpush

@section('content')
    @php
        $input = 'w-full rounded-lg border border-pwb-line bg-white px-4 py-3 text-sm outline-none focus:border-pwb-blue';
        $label = 'mb-1 block text-sm font-bold text-pwb-navy';
        $err   = fn ($f) => $errors->first($f) ? '<p class="mt-1 text-xs text-red-600">'.e($errors->first($f)).'</p>' : '';
    @endphp

    <form method="POST" action="{{ route('pabrik.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-pwb-line bg-white p-6">
            <h2 class="mb-4 font-bold text-pwb-navy"><i class="fa-solid fa-industry mr-2"></i>Data Pabrik</h2>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="nama" class="{{ $label }}">Nama Pabrik</label>
                    <input id="nama" name="nama" value="{{ old('nama', $pabrik['nama']) }}" placeholder="Contoh: Pabrik Semen Conch" class="{{ $input }}">
                    {!! $err('nama') !!}
                </div>
                <div>
                    <label for="alamat" class="{{ $label }}">Alamat</label>
                    <input id="alamat" name="alamat" value="{{ old('alamat', $pabrik['alamat']) }}" placeholder="Dipakai untuk mencari titik kalau peta belum diklik" class="{{ $input }}">
                    {!! $err('alamat') !!}
                </div>
            </div>

            <p class="mt-5 text-xs text-slate-500">Klik lokasi pabrik di peta (atau geser penandanya). Lingkaran = area yang dianggap "di dalam pabrik".</p>
            <div id="peta-pabrik" class="relative z-0 mt-2 h-72 overflow-hidden sm:h-96 rounded-lg border border-pwb-line"></div>
            <input type="hidden" name="lat" id="lat" value="{{ old('lat', $pabrik['lat']) }}">
            <input type="hidden" name="lng" id="lng" value="{{ old('lng', $pabrik['lng']) }}">
            <p id="titik-info" class="mt-2 text-xs text-slate-500"></p>
        </div>

        <div class="mb-5 rounded-xl border border-pwb-line bg-white p-6">
            <h2 class="mb-1 font-bold text-pwb-navy"><i class="fa-solid fa-sliders mr-2"></i>Deteksi Otomatis</h2>
            <p class="mb-4 text-xs text-slate-500">
                Status berubah sendiri dari lokasi HP supir: <b>Menuju Pabrik</b> → masuk area pabrik = <b>Sedang Muat di Pabrik</b>
                → keluar area pabrik = <b>Dalam Pengiriman</b> → masuk area tujuan = <b>Tiba di Tujuan</b> → supir menekan Selesaikan = <b>Terkirim</b>.
            </p>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label for="radius_pabrik" class="{{ $label }}">Radius area pabrik (meter)</label>
                    <input id="radius_pabrik" type="number" min="50" max="5000" name="radius_pabrik" value="{{ old('radius_pabrik', $pabrik['radius_pabrik']) }}" class="{{ $input }}">
                    {!! $err('radius_pabrik') !!}
                </div>
                <div>
                    <label for="radius_tujuan" class="{{ $label }}">Radius area tujuan (meter)</label>
                    <input id="radius_tujuan" type="number" min="50" max="5000" name="radius_tujuan" value="{{ old('radius_tujuan', $pabrik['radius_tujuan']) }}" class="{{ $input }}">
                    {!! $err('radius_tujuan') !!}
                </div>
                <div>
                    <label for="jarak_dekat" class="{{ $label }}">Jarak "mendekati pabrik" (km)</label>
                    <input id="jarak_dekat" type="number" step="0.5" min="1" max="50" name="jarak_dekat" value="{{ old('jarak_dekat', $pabrik['jarak_dekat']) }}" class="{{ $input }}">
                    {!! $err('jarak_dekat') !!}
                    <p class="mt-1 text-[11px] text-slate-500">Truk dalam jarak ini tampil sebagai "Mendekati" di aplikasi Checker.</p>
                </div>
            </div>
        </div>

        {{-- Pengaturan pelacakan aplikasi PWB Supir (dikirim ke HP supir, tanpa perlu update aplikasi) --}}
        <div class="mb-5 rounded-xl border border-pwb-line bg-white p-6">
            <h2 class="mb-1 font-bold text-pwb-navy"><i class="fa-solid fa-satellite-dish mr-2"></i>Pelacakan Aplikasi Supir</h2>
            <p class="mb-4 text-xs text-slate-500">
                Berlaku di semua HP supir dalam beberapa menit setelah disimpan, tanpa perlu memperbarui aplikasi.
                Angka lebih kecil = lebih real time tetapi baterai & kuota lebih boros. Biarkan nilai bawaan bila ragu.
            </p>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach (\App\Support\Pelacakan::NILAI as $k => [$bawaan, $min, $maks, $judul])
                    <div>
                        <label for="lacak_{{ $k }}" class="{{ $label }}">{{ $judul }}</label>
                        <input id="lacak_{{ $k }}" type="number" min="{{ $min }}" max="{{ $maks }}" name="lacak[{{ $k }}]"
                               value="{{ old('lacak.'.$k, $lacak[$k]) }}" class="{{ $input }}">
                        {!! $err('lacak.'.$k) !!}
                        <p class="mt-1 text-[11px] text-slate-500">Bawaan {{ $bawaan }} (boleh {{ $min }}–{{ $maks }})</p>
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="rounded-lg bg-pwb-blue px-6 py-3 text-sm font-bold text-white hover:bg-blue-700">Simpan</button>
    </form>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('peta-pabrik');
        if (!el || typeof L === 'undefined') return;

        var lat = document.getElementById('lat'), lng = document.getElementById('lng');
        var radius = document.getElementById('radius_pabrik');
        var info = document.getElementById('titik-info');

        var map = L.map(el, { scrollWheelZoom: false });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        map.on('click', function () { map.scrollWheelZoom.enable(); });
        map.on('mouseout', function () { map.scrollWheelZoom.disable(); });

        var marker = null, lingkaran = null;

        function pasang(ll) {
            lat.value = ll[0].toFixed(7);
            lng.value = ll[1].toFixed(7);
            if (!marker) {
                marker = L.marker(ll, { draggable: true }).addTo(map);
                marker.on('dragend', function (e) { var p = e.target.getLatLng(); pasang([p.lat, p.lng]); });
                lingkaran = L.circle(ll, { radius: +radius.value || 300, color: '#1553b5', weight: 2, fillOpacity: 0.12 }).addTo(map);
            }
            marker.setLatLng(ll);
            lingkaran.setLatLng(ll);
            info.textContent = 'Titik pabrik: ' + lat.value + ', ' + lng.value;
        }

        map.on('click', function (e) { pasang([e.latlng.lat, e.latlng.lng]); });
        radius.addEventListener('input', function () { if (lingkaran) lingkaran.setRadius(+radius.value || 0); });

        if (lat.value && lng.value) {
            pasang([parseFloat(lat.value), parseFloat(lng.value)]);
            map.setView([parseFloat(lat.value), parseFloat(lng.value)], 16);
        } else {
            map.setView([-0.5022, 117.1536], 8);
            info.textContent = 'Belum ada titik. Klik peta, atau isi alamat lalu simpan untuk dicari otomatis.';
        }
        setTimeout(function () { map.invalidateSize(); }, 200);
    });
    </script>
@endpush
