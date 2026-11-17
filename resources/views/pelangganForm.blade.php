@extends('layoutsAdmin.app')

@section('page_title', $pelanggan->exists ? 'Ubah Pelanggan' : 'Tambah Pelanggan')
@section('title', $pelanggan->exists ? 'Ubah Data Pelanggan' : 'Tambah Pelanggan')
@section('subtitle', 'Lengkapi data pelanggan di bawah ini')

@section('content')
    @php
        $input = 'w-full rounded-lg border border-pwb-line bg-white px-4 py-3 text-sm outline-none focus:border-pwb-blue';
        $label = 'mb-1 block text-sm font-bold text-pwb-navy';
    @endphp

    <form method="POST"
          action="{{ $pelanggan->exists ? route('pelanggan.update', $pelanggan) : route('pelanggan.store') }}"
          class="max-w-3xl rounded-xl border border-pwb-line bg-white p-6">
        @csrf
        @if ($pelanggan->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="nama" class="{{ $label }}">Nama Perusahaan</label>
                <input id="nama" name="nama" value="{{ old('nama', $pelanggan->nama) }}" class="{{ $input }}">
                @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="kontak" class="{{ $label }}">Nama Kontak</label>
                <input id="kontak" name="kontak" value="{{ old('kontak', $pelanggan->kontak) }}" class="{{ $input }}">
                @error('kontak') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="{{ $label }}">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $pelanggan->email) }}" class="{{ $input }}">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="alamat" class="{{ $label }}">Alamat</label>
                <textarea id="alamat" name="alamat" rows="3" class="{{ $input }}">{{ old('alamat', $pelanggan->alamat) }}</textarea>
                @error('alamat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Titik lokasi perusahaan: dicari otomatis dari alamat saat disimpan --}}
            <div class="md:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="{{ $label }} mb-0">Titik Lokasi Perusahaan</span>
                    <button type="button" id="cari-ulang-btn" class="text-xs font-bold text-pwb-blue hover:underline">
                        <i class="fa-solid fa-rotate mr-1"></i>Cari ulang dari alamat
                    </button>
                </div>
                <p id="lokasi-info" class="mb-2 mt-1 text-xs text-slate-500"></p>
                <div id="peta-pelanggan" class="h-72 overflow-hidden rounded-lg border border-pwb-line"></div>
                <p class="mt-1 text-[11px] text-slate-500">Titik ini dipakai sebagai tujuan pengiriman dan untuk mendeteksi truk sudah tiba. Klik peta atau geser penanda kalau lokasinya kurang tepat.</p>

                <input type="hidden" name="lat" id="lat" value="{{ old('lat', $pelanggan->lat) }}">
                <input type="hidden" name="lng" id="lng" value="{{ old('lng', $pelanggan->lng) }}">
                <input type="hidden" name="lokasi_sumber" id="lokasi_sumber" value="{{ old('lokasi_sumber', $pelanggan->lokasi_sumber ?? 'otomatis') }}">
                <input type="hidden" name="cari_ulang" id="cari_ulang" value="0">
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="rounded-lg bg-pwb-blue px-6 py-3 text-sm font-bold text-white hover:bg-blue-700">Simpan</button>
            <a href="{{ route('pelanggan') }}" class="rounded-lg border border-pwb-line px-6 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Batal</a>
        </div>
    </form>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('peta-pelanggan');
        if (!el || typeof L === 'undefined') return;

        var lat = document.getElementById('lat'), lng = document.getElementById('lng');
        var sumber = document.getElementById('lokasi_sumber'), cariUlang = document.getElementById('cari_ulang');
        var info = document.getElementById('lokasi-info');

        var map = L.map(el, { scrollWheelZoom: false });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        map.on('click', function () { map.scrollWheelZoom.enable(); });
        map.on('mouseout', function () { map.scrollWheelZoom.disable(); });

        var marker = null;

        function tulisInfo() {
            if (!lat.value) {
                info.textContent = 'Belum ada titik. Lokasi dicari otomatis dari alamat saat disimpan.';
                info.className = 'mb-2 mt-1 text-xs text-amber-700';
            } else if (sumber.value === 'manual') {
                info.textContent = 'Titik dipilih manual di peta.';
                info.className = 'mb-2 mt-1 text-xs text-slate-500';
            } else {
                info.textContent = 'Titik ditemukan otomatis dari alamat.';
                info.className = 'mb-2 mt-1 text-xs text-green-700';
            }
        }

        function pasang(ll, manual) {
            lat.value = ll[0].toFixed(7);
            lng.value = ll[1].toFixed(7);
            if (manual) { sumber.value = 'manual'; cariUlang.value = '0'; }
            if (marker) { marker.setLatLng(ll); }
            else {
                marker = L.marker(ll, { draggable: true }).addTo(map);
                marker.on('dragend', function (e) { var p = e.target.getLatLng(); pasang([p.lat, p.lng], true); });
            }
            tulisInfo();
        }

        map.on('click', function (e) { pasang([e.latlng.lat, e.latlng.lng], true); });

        document.getElementById('cari-ulang-btn').addEventListener('click', function () {
            lat.value = ''; lng.value = '';
            sumber.value = 'otomatis'; cariUlang.value = '1';
            if (marker) { map.removeLayer(marker); marker = null; }
            tulisInfo();
            info.textContent = 'Lokasi akan dicari ulang dari alamat saat Anda menekan Simpan.';
        });

        if (lat.value && lng.value) {
            pasang([parseFloat(lat.value), parseFloat(lng.value)], false);
            map.setView([parseFloat(lat.value), parseFloat(lng.value)], 15);
        } else {
            map.setView([-0.5022, 117.1536], 6);
        }
        tulisInfo();
        setTimeout(function () { map.invalidateSize(); }, 200);
    });
    </script>
@endpush
