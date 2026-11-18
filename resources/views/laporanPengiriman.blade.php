<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Pengiriman {{ $p->no_resi }} - Putra Wijaya Berjaya</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { pwb: { navy: '#12285C', blue: '#2563EB', red: '#D7263D', sky: '#DCEAFB', bg: '#F4F7FC', line: '#DCE3F0' } },
                    fontFamily: { sans: ['Inter', 'Arial', 'sans-serif'] }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/pwb-tracking-map.css') }}?v={{ filemtime(public_path('css/pwb-tracking-map.css')) }}">
    <style>
        @page { size: A4; margin: 12mm; }
        #peta-laporan { height: 300px; position: relative; z-index: 0; }
        @media print {
            body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .kertas { box-shadow: none !important; border: 0 !important; margin: 0 !important; padding: 0 !important; max-width: none !important; }
            .hindari-potong { break-inside: avoid; page-break-inside: avoid; }
            #peta-laporan { height: 240px; }
            .leaflet-control-zoom { display: none; }
        }
    </style>
</head>
<body class="bg-pwb-bg font-sans text-slate-800">

    {{-- Tombol (tidak ikut tercetak) --}}
    <div class="no-print sticky top-0 z-10 border-b border-pwb-line bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('trackingAdmin', ['resi' => $p->no_resi]) }}" class="text-sm font-bold text-pwb-navy hover:underline">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Tracking
            </a>
            <div class="flex flex-wrap gap-2">
            <a href="{{ route('trackingAdmin.laporanPelanggan', $p) }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">
                <i class="fa-solid fa-paper-plane mr-2"></i>Versi untuk Pelanggan
            </a>
            <button type="button" onclick="window.print()" class="rounded-lg bg-pwb-navy px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-900">
                <i class="fa-solid fa-print mr-2"></i>Cetak / Simpan PDF
            </button>
            </div>
        </div>
    </div>

    <main class="kertas mx-auto my-6 max-w-4xl rounded-xl bg-white p-5 shadow-sm sm:p-10">

        @unless ($lap['selesai_status'])
            <div class="no-print mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Pengiriman ini belum selesai (status: <b>{{ $p->status_label }}</b>). Laporan akan lengkap setelah supir menekan "Selesaikan Pesanan".
            </div>
        @endunless

        {{-- Kop --}}
        <header class="flex flex-wrap items-start justify-between gap-4 border-b-2 border-pwb-navy pb-5">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/pwb-logo.png') }}" alt="PWB" class="h-14 w-auto">
                <div>
                    <p class="text-lg font-extrabold leading-tight text-pwb-navy">PT PUTRA WIJAYA BERJAYA</p>
                    <p class="text-xs text-slate-500">Logistics &amp; Distribution</p>
                </div>
            </div>
            <div class="text-right">
                <h1 class="text-2xl font-extrabold text-pwb-navy">LAPORAN PENGIRIMAN</h1>
                <p class="text-[11px] font-bold uppercase tracking-wide text-red-600">Dokumen internal</p>
                <p class="mt-1 text-sm font-bold">No. Resi: {{ $p->no_resi }}</p>
                <p class="text-xs text-slate-500">Dicetak {{ now()->locale('id')->translatedFormat('d F Y, H.i') }} WITA</p>
            </div>
        </header>

        {{-- Ringkasan --}}
        <section class="hindari-potong mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 print:mt-4 print:grid-cols-4">
            @php $r = $lap['ringkasan']; @endphp
            @foreach ([
                ['Berangkat', $r['berangkat'] ?? '-', 'fa-play'],
                ['Tiba di tujuan', $r['tiba'] ?? '-', 'fa-flag-checkered'],
                ['Total waktu', $r['total'] ?? '-', 'fa-clock'],
                ['Jarak tempuh', $r['jarak'], 'fa-road'],
            ] as [$label, $nilai, $ikon])
                <div class="rounded-lg border border-pwb-line p-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500"><i class="fa-solid {{ $ikon }} mr-1"></i>{{ $label }}</p>
                    <p class="mt-1 text-sm font-extrabold text-pwb-navy">{{ $nilai }}</p>
                </div>
            @endforeach
        </section>
        @if ($r['ketepatan'])
            <p class="mt-3 text-sm">
                Ketepatan waktu:
                <span class="rounded-full px-3 py-0.5 text-xs font-bold {{ $r['ketepatan'][1] === 'green' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $r['ketepatan'][0] }}</span>
                <span class="text-xs text-slate-500">(dibanding estimasi tiba)</span>
            </p>
        @endif

        {{-- Data pengiriman, pelanggan, armada --}}
        <section class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-3 print:mt-4 print:grid-cols-3 print:gap-3">
            @foreach (['Data Pengiriman' => $lap['data'], 'Pelanggan' => $lap['pelanggan'], 'Armada & Supir' => $lap['armada']] as $judul => $isi)
                <div class="hindari-potong rounded-lg border border-pwb-line">
                    <h2 class="rounded-t-lg bg-pwb-sky/60 px-4 py-2 text-sm font-bold text-pwb-navy">{{ $judul }}</h2>
                    <dl class="divide-y divide-pwb-line px-4 text-sm">
                        @foreach ($isi as $k => $v)
                            <div class="py-2 print:py-1">
                                <dt class="text-xs text-slate-500">{{ $k }}</dt>
                                <dd class="font-semibold text-slate-800 print:text-xs">{{ $v }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </section>

        {{-- Durasi per tahap --}}
        <section class="hindari-potong mt-6 print:mt-4">
            <h2 class="mb-2 font-bold text-pwb-navy"><i class="fa-solid fa-stopwatch mr-2"></i>Waktu per Tahap</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-100 text-xs font-bold text-pwb-navy">
                        <tr>
                            <th class="px-3 py-2">Tahap</th>
                            <th class="px-3 py-2">Mulai</th>
                            <th class="px-3 py-2">Selesai</th>
                            <th class="px-3 py-2 text-right">Durasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-pwb-line">
                        @foreach ($lap['tahap'] as $t)
                            <tr>
                                <td class="px-3 py-2 font-semibold">{{ $t['nama'] }}</td>
                                <td class="px-3 py-2">{{ $t['mulai'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $t['selesai'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-right font-bold text-pwb-navy">{{ $t['durasi'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-slate-50">
                            <td class="px-3 py-2 font-extrabold" colspan="3">Total (berangkat sampai selesai)</td>
                            <td class="px-3 py-2 text-right font-extrabold text-pwb-navy">{{ $r['total'] ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Peta rute --}}
        <section class="hindari-potong mt-6">
            <h2 class="mb-2 font-bold text-pwb-navy"><i class="fa-solid fa-map-location-dot mr-2"></i>Peta Perjalanan</h2>
            <div id="peta-laporan" class="overflow-hidden rounded-lg border border-pwb-line"></div>
            <p class="mt-1 text-[11px] text-slate-500">
                Garis biru = rute jalan yang direncanakan. Garis oranye = jejak lokasi HP supir ({{ $r['titik'] }} titik, dikirim tiap ±30 menit).
                Jarak tempuh dihitung dari jejak tersebut, jadi merupakan perkiraan.
            </p>
        </section>

        {{-- Riwayat langkah --}}
        <section class="mt-6 print:mt-4">
            <h2 class="mb-2 font-bold text-pwb-navy"><i class="fa-solid fa-route mr-2"></i>Riwayat Perjalanan</h2>
            <ol class="relative ml-2 border-l-2 border-pwb-line">
                @forelse ($lap['langkah'] as $l)
                    <li class="hindari-potong mb-3 ml-5 print:mb-1.5">
                        <span class="absolute -left-[9px] mt-1 h-4 w-4 rounded-full border-2 border-white {{ $l['selesai'] ? 'bg-pwb-navy' : 'bg-slate-300' }}"></span>
                        <p class="text-sm font-bold {{ $l['selesai'] ? 'text-slate-800' : 'text-slate-400' }}">{{ $l['judul'] }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $l['waktu'] ?? 'Belum dilewati' }}@if ($l['lokasi']) · {{ $l['lokasi'] }}@endif
                        </p>
                    </li>
                @empty
                    <li class="ml-5 text-sm text-slate-500">Belum ada langkah perjalanan.</li>
                @endforelse
            </ol>
        </section>

        {{-- Tanda tangan --}}
        <section class="hindari-potong mt-10 grid grid-cols-3 gap-6 text-center text-sm print:mt-6">
            @foreach (['Admin PWB', 'Supir', 'Penerima'] as $i => $peran)
                <div>
                    <p class="font-bold text-pwb-navy">{{ $peran }}</p>
                    <div class="h-20 print:h-14"></div>
                    <p class="border-t border-slate-400 pt-1 text-xs text-slate-600">
                        {{ $i === 1 ? ($p->truck?->supir?->nama ?? '(.....................)') : '(.....................)' }}
                    </p>
                </div>
            @endforeach
        </section>

        <footer class="mt-8 border-t border-pwb-line pt-3 text-center text-[11px] text-slate-400">
            Laporan ini dibuat otomatis oleh sistem PT Putra Wijaya Berjaya berdasarkan data pengiriman dan lokasi armada.
        </footer>
    </main>

    <script type="application/json" id="data-peta">@json($lap['peta'])</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof L === 'undefined') return;
        var d = JSON.parse(document.getElementById('data-peta').textContent);
        var map = L.map('peta-laporan', { scrollWheelZoom: false });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var titik = [];
        var label = { origin: 'A', destination: 'B', stop: '&bull;' };

        function ikon(cls, isi, size) {
            return L.divIcon({ className: 'pwb-trk-marker-wrap', html: '<div class="pwb-trk-marker ' + cls + '">' + isi + '</div>', iconSize: [size, size], iconAnchor: [size / 2, size / 2] });
        }

        if (Array.isArray(d.route) && d.route.length > 1) {
            L.polyline(d.route, { color: '#ffffff', weight: 8, opacity: 0.9 }).addTo(map);
            L.polyline(d.route, { color: '#1553b5', weight: 4, opacity: 0.9 }).addTo(map);
            titik = titik.concat(d.route);
        }
        if (Array.isArray(d.jejak) && d.jejak.length > 1) {
            L.polyline(d.jejak, { color: '#f97316', weight: 4, opacity: 0.95, dashArray: '6 6' }).addTo(map);
            titik = titik.concat(d.jejak);
        }
        (d.pins || []).forEach(function (p) {
            var t = label[p.type] ? p.type : 'stop';
            L.marker([p.lat, p.lng], { icon: ikon('pwb-trk-marker--' + t, label[t], 30) }).bindTooltip(p.name).addTo(map);
            titik.push([p.lat, p.lng]);
        });

        if (titik.length > 1) map.fitBounds(L.latLngBounds(titik), { padding: [30, 30], maxZoom: 15 });
        else if (titik.length === 1) map.setView(titik[0], 13);
        else map.setView([-0.5022, 117.1536], 8);
        setTimeout(function () { map.invalidateSize(); }, 200);
    });
    </script>
</body>
</html>
