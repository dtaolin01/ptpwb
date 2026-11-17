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
                    <label for="muatan" class="{{ $label }}">Jenis Muatan</label>
                    <input id="muatan" name="muatan" value="{{ $v('muatan') }}" placeholder="Contoh: Semen Conch 50kg (25 ton)" class="{{ $input }}">
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

        {{-- 2. Peta --}}
        <div class="rounded-xl border border-pwb-line bg-white p-6">
            <h2 class="font-bold text-pwb-navy"><i class="fa-solid fa-location-dot mr-2"></i>Titik di Peta</h2>
            <p class="mt-1 text-xs text-slate-500">
                Boleh dikosongkan, sistem mengisi otomatis: <b>Asal</b> = posisi truk saat ini (dari aplikasi supir),
                <b>Tujuan</b> = lokasi perusahaan pelanggan, <b>Posisi Truk</b> = lokasi HP supir tiap 2 jam.
                Status perjalanan (menuju pabrik → muat → pengiriman → tiba) juga berubah sendiri.
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
@endpush
