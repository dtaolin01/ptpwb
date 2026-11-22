@extends('layoutsAdmin.app')

@section('page_title', $pengiriman->exists ? 'Ubah Tracking' : 'Tambah Pengiriman')
@section('title', $pengiriman->exists ? 'Ubah Data Tracking' : 'Tambah Pengiriman')
@section('subtitle', $pengiriman->exists ? 'No. Resi '.$pengiriman->no_resi : 'Lengkapi data pengiriman di bawah ini')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/pwb-tracking-map.css') }}?v={{ filemtime(public_path('css/pwb-tracking-map.css')) }}">
    <style>.pwb-trk-map { height: 380px; }</style>
@endpush

@section('content')
    @php
        $input = 'w-full rounded-lg border border-pwb-line bg-white px-4 py-3 text-sm outline-none focus:border-pwb-blue';
        $kecil = 'w-full rounded-lg border border-pwb-line bg-white px-3 py-2 text-xs outline-none focus:border-pwb-blue';
        $label = 'mb-1 block text-sm font-bold text-pwb-navy';
        $err   = fn ($f) => $errors->first($f) ? '<p class="mt-1 text-xs text-red-600">'.e($errors->first($f)).'</p>' : '';
        $v     = fn ($f) => old($f, $pengiriman->{$f} instanceof \DateTimeInterface ? $pengiriman->{$f}->format('Y-m-d') : $pengiriman->{$f});
    @endphp

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <i class="fa-solid fa-circle-exclamation mr-2"></i>Ada isian yang perlu diperbaiki, lihat tanda merah di bawah.
        </div>
    @endif

    <form method="POST"
          action="{{ $pengiriman->exists ? route('trackingAdmin.update', $pengiriman) : route('trackingAdmin.store') }}"
          class="space-y-5">
        @csrf
        @if ($pengiriman->exists) @method('PUT') @endif

        {{-- 1. Data pengiriman --}}
        <div class="rounded-xl border border-pwb-line bg-white p-6">
            <h2 class="mb-4 font-bold text-pwb-navy"><i class="fa-solid fa-cube mr-2"></i>Data Pengiriman</h2>

            {{-- Rute: lewat pabrik atau dalam kota --}}
            @php $lewat = (string) old('lewat_pabrik', ($pengiriman->lewat_pabrik ?? true) ? '1' : '0'); @endphp
            <p class="{{ $label }}">Rute Pengiriman</p>
            <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-pwb-line p-4 has-[:checked]:border-pwb-blue has-[:checked]:bg-pwb-sky/40">
                    <input type="radio" name="lewat_pabrik" value="1" @checked($lewat === '1') class="rute-pilihan mt-1 h-4 w-4">
                    <span>
                        <span class="block text-sm font-bold text-pwb-navy"><i class="fa-solid fa-industry mr-1"></i> Lewat pabrik</span>
                        <span class="block text-xs text-slate-500">Truk muat dulu di pabrik: Menuju Pabrik → Muat → Pengiriman → Tiba.</span>
                    </span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-pwb-line p-4 has-[:checked]:border-pwb-blue has-[:checked]:bg-pwb-sky/40">
                    <input type="radio" name="lewat_pabrik" value="0" @checked($lewat === '0') class="rute-pilihan mt-1 h-4 w-4">
                    <span>
                        <span class="block text-sm font-bold text-pwb-navy"><i class="fa-solid fa-city mr-1"></i> Dalam kota</span>
                        <span class="block text-xs text-slate-500">Langsung ke tujuan tanpa ke pabrik: Pengiriman → Tiba.</span>
                    </span>
                </label>
            </div>
            {!! $err('lewat_pabrik') !!}

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label for="no_resi" class="{{ $label }}">No. Resi</label>
                    <input id="no_resi" name="no_resi" value="{{ $v('no_resi') }}" placeholder="Kosongkan = dibuat otomatis" class="{{ $input }}">
                    {!! $err('no_resi') !!}
                </div>

                <div>
                    <label for="pelanggan_id" class="{{ $label }}">Pelanggan</label>
                    <select id="pelanggan_id" name="pelanggan_id" class="{{ $input }}">
                        <option value="">Pilih pelanggan</option>
                        @foreach ($pelanggan as $pl)
                            <option value="{{ $pl->id }}" @selected((string) $v('pelanggan_id') === (string) $pl->id)>{{ $pl->nama }}</option>
                        @endforeach
                    </select>
                    {!! $err('pelanggan_id') !!}
                </div>

                <div>
                    <label for="truck_id" class="{{ $label }}">Armada</label>
                    <select id="truck_id" name="truck_id" class="{{ $input }}">
                        <option value="">Belum ditentukan</option>
                        @foreach ($trucks as $t)
                            <option value="{{ $t->id }}" @selected((string) $v('truck_id') === (string) $t->id)>{{ $t->plat }} ({{ $t->jenis }}){{ $t->supir ? ' — '.$t->supir->nama : ' — belum ada supir' }}</option>
                        @endforeach
                    </select>
                    {!! $err('truck_id') !!}
                </div>

                <div>
                    <label for="asal" class="{{ $label }}">Asal</label>
                    <input id="asal" name="asal" value="{{ $v('asal') }}" placeholder="Kosongkan = otomatis dari posisi truk" class="{{ $input }}">
                    {!! $err('asal') !!}
                </div>

                <div>
                    <label for="tujuan" class="{{ $label }}">Tujuan</label>
                    <input id="tujuan" name="tujuan" value="{{ $v('tujuan') }}" placeholder="Kosongkan = otomatis dari alamat pelanggan" class="{{ $input }}">
                    {!! $err('tujuan') !!}
                </div>

                <div>
                    <label for="muatan" class="{{ $label }}">Jenis Muatan <span class="font-normal text-slate-400">(boleh kosong bila daftar barang diisi)</span></label>
                    <input id="muatan" name="muatan" value="{{ $v('muatan') }}" placeholder="Contoh: Semen Conch 50kg" class="{{ $input }}">
                    {!! $err('muatan') !!}
                </div>

                <div>
                    <label for="tanggal_kirim" class="{{ $label }}">Tanggal Kirim</label>
                    <input id="tanggal_kirim" type="date" name="tanggal_kirim" value="{{ $v('tanggal_kirim') }}" class="{{ $input }}">
                    {!! $err('tanggal_kirim') !!}
                </div>

                <div>
                    <label for="estimasi_tiba" class="{{ $label }}">Estimasi Tiba</label>
                    <input id="estimasi_tiba" type="date" name="estimasi_tiba" value="{{ $v('estimasi_tiba') }}" class="{{ $input }}">
                    {!! $err('estimasi_tiba') !!}
                </div>

                <div>
                    <label for="status" class="{{ $label }}">Status</label>
                    <select id="status" name="status" class="{{ $input }}">
                        @foreach (\App\Models\Pengiriman::STATUS as $kode => [$nama])
                            <option value="{{ $kode }}" @selected($v('status') === $kode)>{{ $nama }}</option>
                        @endforeach
                    </select>
                    {!! $err('status') !!}
                </div>
            </div>
        </div>

        {{-- Isian surat jalan --}}
        @php
            $barang = old('barang', $pengiriman->barang ?? []);
            if (! $barang) $barang = [['nama' => '', 'kode' => '', 'qty' => '', 'satuan' => '']];
        @endphp
        <div class="rounded-xl border border-pwb-line bg-white p-6">
            <h2 class="mb-1 font-bold text-pwb-navy"><i class="fa-solid fa-file-lines mr-2"></i>Surat Jalan</h2>
            <p class="mb-4 text-xs text-slate-500">Dicetak di surat jalan dan dikirim ke WhatsApp pelanggan. Sopir & plat diambil dari armada yang dipilih.</p>

            <div class="max-w-md">
                <label for="penjual" class="{{ $label }}">Penjual</label>
                <input id="penjual" name="penjual" value="{{ old('penjual', $pengiriman->penjual) }}" list="pilihan-penjual" autocomplete="off" placeholder="Contoh: PT Semen Conch" class="{{ $input }}">
                <datalist id="pilihan-penjual">@foreach ($penjualLama as $pj)<option value="{{ $pj }}">@endforeach</datalist>
                {!! $err('penjual') !!}
            </div>

            <p class="{{ $label }} mt-5">Daftar Barang</p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="text-left text-xs text-slate-500">
                        <tr><th class="w-8 pb-2">No</th><th class="pb-2">Nama barang</th><th class="w-40 pb-2">Kode barang</th><th class="w-28 pb-2">Qty</th><th class="w-32 pb-2">Satuan</th><th class="w-10"></th></tr>
                    </thead>
                    <tbody id="daftar-barang">
                        @foreach ($barang as $i => $b)
                            <tr class="baris-barang">
                                <td class="no py-1 pr-2 text-xs text-slate-500">{{ $loop->iteration }}</td>
                                <td class="py-1 pr-2"><input name="barang[{{ $i }}][nama]" value="{{ $b['nama'] ?? '' }}" list="katalog-nama" autocomplete="off" placeholder="Ketik / pilih barang" class="isi-nama {{ $kecil }}"></td>
                                <td class="py-1 pr-2"><input name="barang[{{ $i }}][kode]" value="{{ $b['kode'] ?? '' }}" list="katalog-kode" autocomplete="off" placeholder="SMN-50" class="isi-kode {{ $kecil }}"></td>
                                <td class="py-1 pr-2"><input name="barang[{{ $i }}][qty]" value="{{ $b['qty'] ?? '' }}" inputmode="decimal" placeholder="500" class="isi-qty {{ $kecil }}"></td>
                                <td class="py-1 pr-2"><input name="barang[{{ $i }}][satuan]" value="{{ $b['satuan'] ?? '' }}" list="pilihan-satuan" placeholder="sak" class="isi-satuan {{ $kecil }}"></td>
                                <td class="py-1"><button type="button" class="hapus-barang rounded-md px-2 py-1 text-red-600 hover:bg-red-50" title="Hapus baris"><i class="fa-solid fa-xmark"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <datalist id="katalog-nama">@foreach ($katalog as $k)<option value="{{ $k['nama'] }}">{{ trim(($k['kode'] ?? '').' '.($k['satuan'] ?? '')) }}</option>@endforeach</datalist>
            <datalist id="katalog-kode">@foreach ($katalog as $k)@if ($k['kode'])<option value="{{ $k['kode'] }}">{{ $k['nama'] }}</option>@endif @endforeach</datalist>
            <datalist id="pilihan-satuan">
                @foreach (['sak', 'zak', 'ton', 'kg', 'pcs', 'unit', 'batang', 'lembar', 'dus', 'm³', 'rit', 'liter'] as $s)<option value="{{ $s }}">@endforeach
            </datalist>
            @foreach ($errors->get('barang.*') as $pesan) <p class="mt-1 text-xs text-red-600">{{ $pesan[0] }}</p> @break @endforeach
            <button type="button" id="salin-terakhir" class="mt-3 mr-2 hidden rounded-lg border border-pwb-line px-4 py-2 text-xs font-bold text-pwb-blue hover:bg-pwb-bg">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i> Salin dari pengiriman terakhir pelanggan ini
            </button>
            <a href="{{ route('barang') }}" target="_blank" class="mt-3 ml-2 inline-block text-xs font-bold text-slate-500 hover:text-pwb-blue"><i class="fa-solid fa-boxes-stacked mr-1"></i>Kelola Data Barang</a>
            <button type="button" id="tambah-barang" class="mt-3 rounded-lg border border-pwb-line px-4 py-2 text-xs font-bold text-pwb-navy hover:bg-pwb-bg">
                <i class="fa-solid fa-plus mr-1"></i> Tambah barang
            </button>
        </div>

        {{-- 2. Peta --}}
        <div class="rounded-xl border border-pwb-line bg-white p-6">
            <h2 class="font-bold text-pwb-navy"><i class="fa-solid fa-location-dot mr-2"></i>Titik di Peta</h2>
            <p class="mt-1 text-xs text-slate-500">
                Boleh dikosongkan, sistem mengisi otomatis: <b>Asal</b> = posisi truk saat ini (dari aplikasi supir),
                <b>Tujuan</b> = lokasi perusahaan pelanggan, <b>Posisi Truk</b> = lokasi HP supir tiap 30 menit.
                Status perjalanan juga berubah sendiri sesuai rute (lewat pabrik / dalam kota).
                Klik peta atau cari nama tempat hanya kalau ingin mengubah titiknya.
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-2" id="pilih-titik">
                <button type="button" data-titik="asal" class="titik-btn rounded-lg border px-3 py-2 text-xs font-bold">
                    <span class="mr-1 inline-block h-3 w-3 rounded-full bg-[#1553b5] align-middle"></span> Asal (A)
                </button>
                <button type="button" data-titik="tujuan" class="titik-btn rounded-lg border px-3 py-2 text-xs font-bold">
                    <span class="mr-1 inline-block h-3 w-3 rounded-full bg-[#cc2724] align-middle"></span> Tujuan (B)
                </button>
                <button type="button" data-titik="posisi" class="titik-btn rounded-lg border px-3 py-2 text-xs font-bold">
                    <span class="mr-1 inline-block h-3 w-3 rounded-full bg-[#06316b] align-middle"></span> Posisi Truk
                </button>

                <div class="ml-auto flex min-w-[260px] flex-1 overflow-hidden rounded-lg border border-pwb-line md:flex-none">
                    <input type="text" id="cari-lokasi" placeholder="Cari tempat, contoh: Pelabuhan Samarinda" class="flex-1 px-3 py-2 text-xs outline-none">
                    <button type="button" id="cari-btn" class="bg-pwb-blue px-4 text-xs text-white hover:bg-blue-700">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </div>
            <p id="cari-info" class="mt-2 hidden text-xs"></p>

            <div id="peta-picker" class="pwb-trk-map mt-4"></div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                @foreach (['asal' => 'Asal', 'tujuan' => 'Tujuan', 'posisi' => 'Posisi Truk'] as $titik => $nama)
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-bold text-pwb-navy">{{ $nama }}</span>
                            <button type="button" data-hapus="{{ $titik }}" class="text-xs text-red-600 hover:underline">Kosongkan</button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input name="{{ $titik }}_lat" id="{{ $titik }}_lat" value="{{ old($titik.'_lat', $pengiriman->{$titik.'_lat'}) }}" placeholder="Latitude" class="{{ $kecil }}">
                            <input name="{{ $titik }}_lng" id="{{ $titik }}_lng" value="{{ old($titik.'_lng', $pengiriman->{$titik.'_lng'}) }}" placeholder="Longitude" class="{{ $kecil }}">
                        </div>
                        {!! $err($titik.'_lat') !!}{!! $err($titik.'_lng') !!}
                        @if ($titik === 'posisi' && $pengiriman->posisi_update)
                            <p class="mt-1 text-[11px] text-slate-500">Terakhir diperbarui {{ $pengiriman->posisi_update->locale('id')->translatedFormat('j M Y, H:i') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 3. Langkah perjalanan --}}
        <div class="rounded-xl border border-pwb-line bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-pwb-navy"><i class="fa-solid fa-route mr-2"></i>Status Perjalanan</h2>
                    <p class="mt-1 text-xs text-slate-500">Centang "Selesai" untuk langkah yang sudah dilewati. Urutan di sini = urutan di halaman Tracking.</p>
                </div>
                <button type="button" id="tambah-langkah" class="rounded-lg border border-pwb-navy px-4 py-2 text-xs font-bold text-pwb-navy hover:bg-pwb-sky">
                    <i class="fa-solid fa-plus mr-1"></i> Tambah Langkah
                </button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs font-bold text-pwb-navy">
                        <tr>
                            <th class="w-10 px-2 py-2">#</th>
                            <th class="px-2 py-2">Judul Langkah</th>
                            <th class="px-2 py-2">Waktu</th>
                            <th class="px-2 py-2">Lokasi</th>
                            <th class="px-2 py-2 text-center">Selesai</th>
                            <th class="px-2 py-2"></th>
                        </tr>
                    </thead>
                    <tbody id="daftar-langkah">
                        @foreach ($events as $i => $e)
                            <tr class="langkah border-t border-pwb-line">
                                <td class="nomor px-2 py-2 text-xs text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-2 py-2">
                                    <input name="events[{{ $i }}][judul]" value="{{ $e['judul'] ?? '' }}" class="{{ $kecil }}">
                                    {!! $err("events.$i.judul") !!}
                                </td>
                                <td class="px-2 py-2"><input type="datetime-local" name="events[{{ $i }}][waktu]" value="{{ $e['waktu'] ?? '' }}" class="{{ $kecil }}"></td>
                                <td class="px-2 py-2"><input name="events[{{ $i }}][lokasi]" value="{{ $e['lokasi'] ?? '' }}" class="{{ $kecil }}"></td>
                                <td class="px-2 py-2 text-center">
                                    <input type="hidden" name="events[{{ $i }}][selesai]" value="0">
                                    <input type="checkbox" name="events[{{ $i }}][selesai]" value="1" @checked(! empty($e['selesai'])) class="h-4 w-4">
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <button type="button" class="hapus-langkah text-red-600 hover:text-red-800" title="Hapus langkah"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <template id="tpl-langkah">
                <tr class="langkah border-t border-pwb-line">
                    <td class="nomor px-2 py-2 text-xs text-slate-500"></td>
                    <td class="px-2 py-2"><input name="events[__i__][judul]" class="{{ $kecil }}"></td>
                    <td class="px-2 py-2"><input type="datetime-local" name="events[__i__][waktu]" class="{{ $kecil }}"></td>
                    <td class="px-2 py-2"><input name="events[__i__][lokasi]" class="{{ $kecil }}"></td>
                    <td class="px-2 py-2 text-center">
                        <input type="hidden" name="events[__i__][selesai]" value="0">
                        <input type="checkbox" name="events[__i__][selesai]" value="1" class="h-4 w-4">
                    </td>
                    <td class="px-2 py-2 text-right">
                        <button type="button" class="hapus-langkah text-red-600 hover:text-red-800" title="Hapus langkah"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            </template>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-pwb-blue px-6 py-3 text-sm font-bold text-white hover:bg-blue-700">Simpan</button>
            <a href="{{ $pengiriman->exists ? route('trackingAdmin', ['resi' => $pengiriman->no_resi]) : route('trackingAdmin') }}"
               class="rounded-lg border border-pwb-line bg-white px-6 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Batal</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script src="{{ asset('js/admin-tracking-form.js') }}?v={{ filemtime(public_path('js/admin-tracking-form.js')) }}"></script>
    <script>
    // Daftar barang surat jalan: tambah / hapus baris
    document.addEventListener('DOMContentLoaded', function () {
        var tbody = document.getElementById('daftar-barang');
        function nomori() {
            tbody.querySelectorAll('tr.baris-barang').forEach(function (tr, i) {
                tr.querySelector('.no').textContent = i + 1;
                tr.querySelectorAll('input').forEach(function (inp) { inp.name = inp.name.replace(/barang\[\d+\]/, 'barang[' + i + ']'); });
            });
        }
        // Isi otomatis kode & satuan dari Data Barang
        var katalog = @json($katalog);
        var cari = function (kolom, nilai) {
            nilai = (nilai || '').trim().toLowerCase();
            return nilai ? katalog.find(function (k) { return (k[kolom] || '').toLowerCase() === nilai; }) : null;
        };
        tbody.addEventListener('change', function (e) {
            var tr = e.target.closest('tr.baris-barang');
            if (!tr) return;
            var k = e.target.classList.contains('isi-nama') ? cari('nama', e.target.value)
                  : e.target.classList.contains('isi-kode') ? cari('kode', e.target.value) : null;
            if (!k) return;
            var isi = function (sel, v) { var el = tr.querySelector(sel); if (el && v && !el.value) el.value = v; };
            if (e.target.classList.contains('isi-kode')) isi('.isi-nama', k.nama);
            isi('.isi-kode', k.kode);
            isi('.isi-satuan', k.satuan);
            var qty = tr.querySelector('.isi-qty'); if (qty && !qty.value) qty.focus();
        });

        // Salin penjual & barang dari pengiriman terakhir pelanggan yang dipilih
        var terakhir = @json((object) $terakhir);
        var pilihPelanggan = document.getElementById('pelanggan_id');
        var tombolSalin = document.getElementById('salin-terakhir');
        function cekSalin() { tombolSalin.classList.toggle('hidden', !terakhir[pilihPelanggan.value]); }
        pilihPelanggan.addEventListener('change', cekSalin); cekSalin();
        tombolSalin.addEventListener('click', function () {
            var t = terakhir[pilihPelanggan.value]; if (!t) return;
            var pj = document.getElementById('penjual'); if (t.penjual && !pj.value) pj.value = t.penjual;
            var contoh = tbody.querySelector('tr.baris-barang');
            tbody.querySelectorAll('tr.baris-barang').forEach(function (tr, i) { if (i > 0) tr.remove(); });
            (t.barang || []).forEach(function (b, i) {
                var tr = i === 0 ? contoh : contoh.cloneNode(true);
                tr.querySelector('.isi-nama').value = b.nama || '';
                tr.querySelector('.isi-kode').value = b.kode || '';
                tr.querySelector('.isi-qty').value = b.qty || '';
                tr.querySelector('.isi-satuan').value = b.satuan || '';
                if (i > 0) tbody.appendChild(tr);
            });
            nomori();
        });

        document.getElementById('tambah-barang').addEventListener('click', function () {
            var baru = tbody.querySelector('tr.baris-barang').cloneNode(true);
            baru.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
            tbody.appendChild(baru);
            nomori();
            baru.querySelector('input').focus();
        });
        tbody.addEventListener('click', function (e) {
            var tombol = e.target.closest('.hapus-barang');
            if (!tombol) return;
            var tr = tombol.closest('tr');
            if (tbody.querySelectorAll('tr.baris-barang').length > 1) tr.remove();
            else tr.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
            nomori();
        });
    });

    // Rute dalam kota → sembunyikan langkah pabrik yang belum dilewati (tidak ikut disimpan)
    document.addEventListener('DOMContentLoaded', function () {
        var langkahPabrik = @json(\App\Models\Pengiriman::LANGKAH_PABRIK);
        var tbody = document.getElementById('daftar-langkah');

        function terapkan() {
            var pilih = document.querySelector('input.rute-pilihan:checked');
            var dalamKota = pilih && pilih.value === '0';
            tbody.querySelectorAll('tr.langkah').forEach(function (tr) {
                var judul = tr.querySelector('input[name$="[judul]"]');
                var selesai = tr.querySelector('input[type=checkbox][name$="[selesai]"]');
                var pabrik = judul && langkahPabrik.indexOf(judul.value.trim()) >= 0 && !(selesai && selesai.checked);
                var sembunyi = dalamKota && pabrik;
                tr.style.display = sembunyi ? 'none' : '';
                tr.querySelectorAll('input').forEach(function (i) { i.disabled = sembunyi; });
            });
            // nomor urut hanya untuk baris yang terlihat
            var n = 0;
            tbody.querySelectorAll('tr.langkah').forEach(function (tr) {
                if (tr.style.display !== 'none') tr.querySelector('.nomor').textContent = ++n;
            });
        }

        document.querySelectorAll('input.rute-pilihan').forEach(function (r) { r.addEventListener('change', terapkan); });
        terapkan();
    });
    </script>
@endpush
