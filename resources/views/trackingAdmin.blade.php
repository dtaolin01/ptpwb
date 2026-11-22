@extends('layoutsAdmin.app')

@section('page_title', 'Tracking')
@section('title', 'Tracking Pengiriman')
@section('subtitle', 'Lacak dan perbarui status pengiriman. Perubahan langsung tampil di halaman Tracking publik.')

@section('banner_action')
    <a href="{{ route('trackingAdmin.create') }}" class="inline-flex items-center gap-3 rounded-lg bg-pwb-navy px-5 py-3 text-sm font-bold text-white hover:bg-slate-900">
        <i class="fa-solid fa-plus"></i> Tambah Pengiriman
    </a>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/pwb-tracking-map.css') }}?v={{ filemtime(public_path('css/pwb-tracking-map.css')) }}">
    <style>.pwb-trk-map { height: 420px; }</style>
@endpush

@section('content')
    {{-- Pencarian resi --}}
    <form action="{{ route('trackingAdmin') }}" method="GET" class="flex overflow-hidden rounded-lg border border-pwb-line bg-white">
        <input type="text" name="resi" value="{{ $resi }}" placeholder="Masukkan nomor resi, contoh: PWB-20261117-0001"
               class="flex-1 px-4 py-3 text-sm outline-none placeholder:text-slate-400">
        <button type="submit" class="bg-pwb-blue px-6 text-white hover:bg-blue-700">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </form>

    @if ($detail)
    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
        {{-- Detail pengiriman --}}
        <div class="rounded-xl border border-pwb-line bg-white p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="flex items-center gap-3 font-bold text-pwb-navy">
                    <i class="fa-solid fa-cube text-lg"></i> Detail Pengiriman
                </h2>
                <x-badge :warna="$detail['warna']">{{ $detail['status'] }}</x-badge>
            </div>

            {{-- Aksi: Ubah selalu ada; laporan aktif penuh setelah pengiriman selesai --}}
            <div class="mt-4 flex flex-col gap-2 border-b border-pwb-line pb-4 sm:flex-row sm:flex-wrap">
                <a href="{{ route('trackingAdmin.edit', $pengiriman) }}"
                   class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg sm:flex-1 bg-pwb-blue px-3 py-2 text-xs font-bold text-white hover:bg-blue-700">
                    <i class="fa-solid fa-pen"></i> Ubah Pengiriman
                </a>
                <a href="{{ route('trackingAdmin.laporan', $pengiriman) }}" target="_blank"
                   class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg sm:flex-1 border border-pwb-line bg-white px-3 py-2 text-xs font-bold text-pwb-navy hover:bg-pwb-bg">
                    <i class="fa-solid fa-file-lines"></i> Laporan Internal
                </a>
                <a href="{{ route('trackingAdmin.laporanPelanggan', $pengiriman) }}" target="_blank" title="Laporan PDF untuk dikirim ke pelanggan"
                   class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg sm:flex-1 px-3 py-2 text-xs font-bold {{ $pengiriman->status === 'terkirim' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'border border-pwb-line bg-white text-pwb-navy hover:bg-pwb-bg' }}">
                    <i class="fa-solid fa-paper-plane"></i> Kirim ke Pelanggan
                </a>
            </div>

            {{-- Foto bukti dari aplikasi supir --}}
            @php $fotoBukti = $pengiriman->buktiFoto; @endphp
            <div class="mt-3 border-b border-pwb-line pb-4">
                <p class="mb-2 text-xs font-bold text-pwb-navy"><i class="fa-solid fa-camera mr-1"></i> Foto bukti dari sopir ({{ $fotoBukti->count() }})</p>
                @if ($fotoBukti->isEmpty())
                    <p class="text-[11px] text-slate-500">Belum ada foto. Sopir mengirim foto dari aplikasi saat barang sampai.</p>
                @else
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($fotoBukti as $f)
                            <div class="group relative overflow-hidden rounded-lg border border-pwb-line">
                                <a href="{{ $f->url() }}" target="_blank"><img src="{{ $f->url() }}" loading="lazy" class="aspect-square w-full object-cover" alt="Foto bukti"></a>
                                <form method="POST" action="{{ route('bukti.hapus', $f) }}" onsubmit="return confirm('Hapus foto ini?')" class="absolute right-1 top-1">
                                    @csrf @method('DELETE')
                                    <button class="rounded bg-white/90 px-1.5 py-0.5 text-[10px] text-red-600" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                </form>
                                <div class="bg-white px-1.5 py-1 text-[10px] leading-snug text-slate-600">
                                    <p class="font-bold text-pwb-navy">{{ $f->waktuLabel() }}</p>
                                    @if ($f->alamat)
                                        <p>{{ $f->alamat }}</p>
                                    @endif
                                    <p class="text-slate-400">
                                        @if ($f->linkPeta())<a href="{{ $f->linkPeta() }}" target="_blank" class="text-pwb-blue hover:underline">Peta</a> · @endif
                                        {{ $f->sumber_lokasi === 'truk' ? 'lokasi GPS truk' : ($f->sumber_lokasi === 'hp' ? 'lokasi HP' : 'tanpa lokasi') }} · {{ (int) round($f->ukuran / 1024) }} KB
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Surat jalan + status WhatsApp otomatis --}}
            <div class="mt-3 flex flex-col gap-2 border-b border-pwb-line pb-4 sm:flex-row sm:flex-wrap sm:items-center">
                <a href="{{ route('suratJalan', $pengiriman) }}" target="_blank"
                   class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg sm:flex-1 border border-pwb-line bg-white px-3 py-2 text-xs font-bold text-pwb-navy hover:bg-pwb-bg">
                    <i class="fa-solid fa-print"></i> Surat Jalan
                </a>
                <form method="POST" action="{{ route('suratJalan.kirim', $pengiriman) }}" class="sm:flex-1"
                      onsubmit="return confirm('Kirim surat jalan ke WhatsApp pelanggan?')">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-green-600 px-3 py-2 text-xs font-bold text-white hover:bg-green-700">
                        <i class="fa-brands fa-whatsapp"></i> {{ $pengiriman->surat_jalan_wa_at ? 'Kirim Ulang WA' : 'Kirim Surat Jalan WA' }}
                    </button>
                </form>
                <p class="w-full text-[11px] {{ $pengiriman->surat_jalan_wa_at ? 'text-green-700' : 'text-slate-500' }}">
                    <i class="fa-brands fa-whatsapp"></i>
                    @if ($pengiriman->surat_jalan_wa_status)
                        {{ $pengiriman->surat_jalan_wa_status }}@if ($pengiriman->surat_jalan_wa_at) · {{ $pengiriman->surat_jalan_wa_at->format('d/m H.i') }}@endif
                    @else
                        Surat jalan belum dikirim lewat WhatsApp.
                    @endif
                </p>
                <p class="w-full text-[11px] {{ $pengiriman->ttd_at ? 'font-bold text-green-700' : 'text-slate-500' }}">
                    <i class="fa-solid fa-signature"></i>
                    {{ $pengiriman->ttd_at ? 'Ditandatangani penerima: '.$pengiriman->ttd_nama.' · '.$pengiriman->ttd_at->format('d/m H.i') : 'Belum ditandatangani penerima.' }}
                </p>
            </div>

            <dl class="mt-5 space-y-4 text-sm">
                @foreach ([
                    'No. Resi'      => $detail['no_resi'],
                    'Pelanggan'     => $detail['pelanggan'],
                    'Asal'          => $detail['asal'],
                    'Tujuan'        => $detail['tujuan'],
                    'Rute'          => $detail['rute'],
                    'Jenis Muatan'  => $detail['muatan'],
                    'Armada'        => $detail['armada'],
                    'Estimasi Tiba' => $detail['estimasi'],
                ] as $label => $nilai)
                    <div class="grid grid-cols-2">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="font-bold">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>

            <a href="{{ route('tracking', ['resi' => $detail['no_resi']]) }}#lacak" target="_blank" rel="noopener"
               class="mt-5 inline-flex items-center gap-2 text-xs font-bold text-pwb-blue hover:underline">
                Lihat di halaman Tracking publik <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>

        {{-- Status perjalanan --}}
        <div class="rounded-xl border border-pwb-line bg-white p-5">
            <h2 class="font-bold text-pwb-navy">Status Perjalanan</h2>

            <ol class="mt-5">
                @forelse ($perjalanan as $step)
                    @php $selesai = $step['state'] === 'selesai'; @endphp
                    <li class="relative flex gap-4 pb-6 last:pb-0">
                        @unless ($loop->last)
                            <span class="absolute left-[15px] top-8 h-full w-0.5 bg-slate-300"></span>
                        @endunless
                        <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs text-white {{ $selesai ? 'bg-pwb-navy' : 'bg-slate-300' }}">
                            @if ($selesai) <i class="fa-solid fa-check"></i> @endif
                        </span>
                        <div>
                            <p class="text-sm font-bold {{ $selesai ? '' : 'text-slate-500' }}">{{ $step['judul'] }}</p>
                            <p class="text-xs {{ $selesai ? 'text-pwb-blue' : 'text-slate-400' }}">{{ $step['waktu'] }}</p>
                            @if ($step['lokasi'])
                                <p class="text-xs text-slate-400">{{ $step['lokasi'] }}</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Belum ada langkah perjalanan.</li>
                @endforelse
            </ol>
        </div>
    </div>

    {{-- Peta: sama dengan halaman Tracking publik (Leaflet + rute jalan) --}}
    <div class="mt-5 rounded-xl border border-pwb-line bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="flex items-center gap-3 font-bold text-pwb-navy">
                <i class="fa-solid fa-location-dot"></i> Lokasi Terakhir
            </h2>
            <span class="text-xs text-slate-500">Update: {{ $updateTerakhir }}</span>
        </div>

        @if (empty($peta['pins']) && empty($peta['trucks']))
            <p class="mt-3 rounded-lg bg-amber-50 px-4 py-3 text-xs text-amber-700">
                Titik asal, tujuan, dan posisi truk belum diatur.
                <a href="{{ route('trackingAdmin.edit', $pengiriman) }}" class="font-bold underline">Atur di halaman Ubah</a>.
            </p>
        @endif

        <div id="pwb-trk-map" class="pwb-trk-map mt-4" data-map="{{ json_encode($peta) }}"></div>
    </div>
    @elseif ($resi !== '')
        <div class="mt-5 rounded-xl border border-pwb-line bg-white p-8 text-center text-sm text-slate-500">
            Nomor resi <span class="font-bold text-slate-800">{{ $resi }}</span> tidak ditemukan.
        </div>
    @endif

    {{-- Daftar pengiriman --}}
    <div class="mt-5 rounded-xl border border-pwb-line bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-bold text-pwb-navy">Semua Pengiriman</h2>

            <form method="GET" action="{{ route('trackingAdmin') }}" class="flex items-center gap-2">
                @if ($resi !== '') <input type="hidden" name="resi" value="{{ $resi }}"> @endif
                <select name="status" onchange="this.form.submit()" class="rounded-lg border border-pwb-line px-3 py-2 text-xs">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\Pengiriman::STATUS as $kode => [$nama])
                        <option value="{{ $kode }}" @selected($status === $kode)>{{ $nama }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100/80 text-xs font-bold text-pwb-navy">
                    <tr>
                        <th class="px-4 py-3">No. Resi</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3">Rute</th>
                        <th class="px-4 py-3">Tgl Kirim</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-pwb-line">
                    @forelse ($daftar as $p)
                        <tr class="{{ $pengiriman && $pengiriman->id === $p->id ? 'bg-pwb-sky/40' : '' }}">
                            <td class="px-4 py-4">
                                <a href="{{ route('trackingAdmin', ['resi' => $p->no_resi, 'status' => $status ?: null, 'page' => $daftar->currentPage()]) }}"
                                   class="font-bold text-pwb-blue hover:underline">{{ $p->no_resi }}</a>
                            </td>
                            <td class="px-4 py-4">{{ $p->pelanggan?->nama }}</td>
                            <td class="px-4 py-4 text-xs">{{ $p->asal ?: '-' }} → {{ $p->tujuan }}</td>
                            <td class="px-4 py-4 text-xs">{{ $p->tanggal_kirim?->format('d/m/Y') }}</td>
                            <td class="px-4 py-4"><x-badge :warna="$p->status_warna">{{ $p->status_label }}</x-badge></td>
                            <td class="px-4 py-4">
                                @if ($p->status === 'terkirim')
                                    <a href="{{ route('trackingAdmin.laporan', $p) }}" target="_blank" title="Laporan pengiriman"
                                       class="mb-2 inline-flex items-center gap-1 rounded-md bg-green-600 px-2 py-1 text-xs font-bold text-white hover:bg-green-700">
                                        <i class="fa-solid fa-file-lines"></i> Laporan
                                    </a>
                                    <a href="{{ route('trackingAdmin.laporanPelanggan', $p) }}" target="_blank" title="Kirim laporan PDF ke pelanggan"
                                       class="mb-2 inline-flex items-center gap-1 rounded-md bg-emerald-700 px-2 py-1 text-xs font-bold text-white hover:bg-emerald-800">
                                        <i class="fa-solid fa-paper-plane"></i> Ke Pelanggan
                                    </a>
                                    @if ($p->laporan_dikirim_at)
                                        <span class="mb-2 block text-[11px] text-green-700" title="{{ $p->laporan_dikirim_ke }}">
                                            <i class="fa-solid fa-check"></i> Laporan terkirim {{ $p->laporan_dikirim_at->format('d/m H.i') }}
                                        </span>
                                    @endif
                                @endif
                                @include('partialsadmin.aksi', [
                                    'edit'  => route('trackingAdmin.edit', $p),
                                    'hapus' => route('trackingAdmin.destroy', $p),
                                    'nama'  => 'pengiriman '.$p->no_resi,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada pengiriman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partialsadmin.pagination', ['data' => $daftar])
    </div>
@endsection

@push('scripts')
    @if ($detail)
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
        <script src="{{ asset('js/pwb-tracking-map.js') }}?v={{ filemtime(public_path('js/pwb-tracking-map.js')) }}"></script>
    @endif
@endpush
