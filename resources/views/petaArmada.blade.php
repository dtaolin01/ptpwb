@extends('layoutsAdmin.app')

@section('page_title', 'Peta Armada')
@section('title', 'Peta Armada')
@section('subtitle', 'Lokasi terakhir semua truk dari aplikasi supir. Arahkan pointer (atau ketuk di HP) ke ikon truk untuk melihat info armada.')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
    <style>
        .armada-ikon { background: none; border: 0; }
        .armada-ikon .bulat {
            width: 40px; height: 40px; margin: 0 auto; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.35);
            background: #06316b; font-size: 19px;
        }
        .armada-ikon.jalan .bulat { background: #15803d; }
        .armada-ikon.lama .bulat  { background: #94a3b8; }
        .armada-ikon .plat {
            margin-top: 3px; white-space: nowrap; text-align: center;
            font: 700 11px/1.4 Inter, Arial, sans-serif; color: #12285C;
            background: #fff; border-radius: 6px; padding: 0 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,.25); display: inline-block;
        }
        .armada-ikon .plat-wrap { text-align: center; }
        .pabrik-ikon { background: none; border: 0; }
        .pabrik-ikon div {
            width: 36px; height: 36px; border-radius: 50%; background: #f0cc1b; color: #1f2937;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
            border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.35);
        }
        .leaflet-tooltip.armada-info { padding: 0; border: 0; border-radius: 12px; box-shadow: 0 8px 24px rgba(18,40,92,.25); white-space: normal; }
        .leaflet-tooltip.armada-info::before { display: none; }
        #peta-armada { height: 62vh; min-height: 380px; position: relative; z-index: 0; }
    </style>
@endpush

@section('content')
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        {{-- Peta --}}
        <div class="rounded-xl border border-pwb-line bg-white p-3 sm:p-4 xl:col-span-2">
            <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-600">
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#15803d]"></span>Ada pesanan</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#06316b]"></span>Tanpa pesanan</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-slate-400"></span>Lokasi &gt; 1 jam lalu</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#f0cc1b]"></span>Pabrik</span>
                <span class="ml-auto text-slate-500">Diperbarui <span id="jam-update">{{ $peta['diperbarui'] }}</span> · otomatis tiap 1 menit</span>
            </div>
            <div class="relative">
                <div id="peta-armada" class="overflow-hidden rounded-lg"></div>

                {{-- Panel info untuk layar HP (muncul saat ikon truk diketuk) --}}
                <div id="info-hp" class="absolute inset-x-2 bottom-2 z-[500] hidden rounded-xl bg-white shadow-2xl">
                    <button type="button" data-tutup aria-label="Tutup"
                            class="absolute right-1 top-1 z-10 flex h-8 w-8 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <div data-isi class="[&>div]:w-full"></div>
                </div>
            </div>
        </div>

        {{-- Daftar truk --}}
        <div class="rounded-xl border border-pwb-line bg-white p-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-pwb-navy"><i class="fa-solid fa-truck mr-2"></i>Daftar Truk</h2>
                <span id="jumlah-truk" class="text-xs text-slate-500"></span>
            </div>
            <input id="cari-truk" type="search" placeholder="Cari plat atau nama supir..."
                   class="mt-3 w-full rounded-lg border border-pwb-line px-3 py-2 text-sm outline-none focus:border-pwb-blue">
            <ul id="daftar-truk" class="mt-3 max-h-[62vh] divide-y divide-pwb-line overflow-y-auto"></ul>
        </div>
    </div>

    <script type="application/json" id="data-peta">@json($peta)</script>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof L === 'undefined') return;

        var data = JSON.parse(document.getElementById('data-peta').textContent);
        var urlData = @json(route('petaArmada.data'));

        var map = L.map('peta-armada', { scrollWheelZoom: false });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        map.on('click', function () { map.scrollWheelZoom.enable(); });
        map.on('mouseout', function () { map.scrollWheelZoom.disable(); });

        var lapisan = L.layerGroup().addTo(map);
        var marker = {};          // id truk → marker
        var sudahDipusatkan = false;

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        var warnaBadge = { green: 'bg-green-100 text-green-700', blue: 'bg-blue-100 text-blue-700', red: 'bg-red-100 text-red-600', amber: 'bg-amber-100 text-amber-700' };

        // Kartu info yang muncul saat pointer di atas ikon truk
        function info(t) {
            var p = t.pesanan;
            return '<div class="w-64 max-w-full overflow-hidden rounded-xl bg-white text-xs text-slate-700">' +
                '<div class="p-3">' +
                    '<div class="flex items-center gap-3">' +
                        (t.foto ? '<img src="' + esc(t.foto) + '" class="h-11 w-16 shrink-0 rounded-md object-cover" onerror="this.remove()">' : '') +
                        '<div class="min-w-0">' +
                            '<div class="text-base font-extrabold leading-tight text-pwb-navy">' + esc(t.plat) + '</div>' +
                            '<div class="text-slate-500">' + esc(t.jenis) + ' · ' + esc(t.kapasitas) + ' · ' + esc(t.status) + '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="mt-2"><i class="fa-solid fa-user mr-1 text-pwb-navy"></i><b>' + esc(t.supir) + '</b>' +
                        (t.telepon ? ' · ' + esc(t.telepon) : '') + '</div>' +
                    (p
                        ? '<div class="mt-2 rounded-lg bg-slate-50 p-2">' +
                              '<div class="flex items-center justify-between gap-2"><b class="text-pwb-navy">' + esc(p.no_resi) + '</b>' +
                              '<span class="rounded-full px-2 py-0.5 text-[10px] font-bold ' + (warnaBadge[p.warna] || '') + '">' + esc(p.status) + '</span></div>' +
                              '<div class="mt-1">' + esc(p.rute) + '</div>' +
                              '<div class="text-slate-500">' + esc(p.pelanggan || '-') + ' · ' + esc(p.muatan) + '</div>' +
                          '</div>'
                        : '<div class="mt-2 text-slate-400">Tidak ada pesanan aktif</div>') +
                    (t.zona ? '<div class="mt-2 font-bold text-amber-700"><i class="fa-solid fa-industry mr-1"></i>' + esc(t.zona) + '</div>' : '') +
                    '<div class="mt-2 text-[11px] ' + (t.lama ? 'text-red-600' : 'text-slate-400') + '">' +
                        '<i class="fa-regular fa-clock mr-1"></i>Lokasi ' + esc(t.waktu_relatif || '-') + (t.waktu ? ' (' + esc(t.waktu) + ' WITA)' : '') +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        function ikon(t) {
            var kelas = t.lama ? 'lama' : (t.pesanan ? 'jalan' : '');
            return L.divIcon({
                className: 'armada-ikon ' + kelas,
                html: '<div class="bulat">&#128666;</div><div class="plat-wrap"><span class="plat">' + esc(t.plat) + '</span></div>',
                iconSize: [90, 62],
                iconAnchor: [45, 22],
                tooltipAnchor: [0, -22]
            });
        }

        var petaEl = document.getElementById('peta-armada');
        var panel = document.getElementById('info-hp');
        function layarKecil() { return petaEl.clientWidth < 600; }

        // HP: info tampil sebagai panel di bawah peta (kartu melayang tidak muat di layar sempit)
        function tampilPanel(t) {
            panel.querySelector('[data-isi]').innerHTML = info(t);
            panel.classList.remove('hidden');
        }
        panel.querySelector('[data-tutup]').addEventListener('click', function () { panel.classList.add('hidden'); });
        map.on('click', function () { panel.classList.add('hidden'); });

        // Laptop: kartu dibuka ke arah yang masih muat di dalam peta, dihitung dari ukuran kartu sebenarnya
        function aturArah(m) {
            var tt = m.getTooltip(), el = tt && tt.getElement();
            if (!el) return;
            var w = el.offsetWidth, h = el.offsetHeight;
            var pt = map.latLngToContainerPoint(m.getLatLng()), size = map.getSize(), jeda = 30;
            var muatDatar = pt.x - w / 2 > 4 && pt.x + w / 2 < size.x - 4;
            var arah = 'top', offset = [0, -24];
            if (muatDatar && pt.y - h - jeda > 0)                 { arah = 'top';    offset = [0, -24]; }
            else if (muatDatar && pt.y + h + 50 < size.y)         { arah = 'bottom'; offset = [0, 44]; }
            else if (pt.x + w + jeda < size.x)                    { arah = 'right';  offset = [26, 0]; }
            else if (pt.x - w - jeda > 0)                         { arah = 'left';   offset = [-26, 0]; }
            if (tt.options.direction !== arah) {
                tt.options.direction = arah;
                tt.options.offset = L.point(offset);
                tt.update();
            }
        }

        function gambar() {
            lapisan.clearLayers();
            marker = {};
            var titik = [];

            if (data.pabrik) {
                var pb = [data.pabrik.lat, data.pabrik.lng];
                L.circle(pb, { radius: data.pabrik.radius, color: '#1553b5', weight: 2, fillOpacity: 0.08 }).addTo(lapisan);
                L.marker(pb, {
                    icon: L.divIcon({ className: 'pabrik-ikon', html: '<div><i class="fa-solid fa-industry"></i></div>', iconSize: [36, 36], iconAnchor: [18, 18] })
                }).bindTooltip('<b>' + esc(data.pabrik.nama) + '</b><br>Radius ' + data.pabrik.radius + ' m', { direction: 'top', offset: [0, -16] }).addTo(lapisan);
                titik.push(pb);
            }

            data.truk.forEach(function (t) {
                if (t.lat === null) return;
                var m = L.marker([t.lat, t.lng], { icon: ikon(t), zIndexOffset: t.pesanan ? 1000 : 0 })
                    .bindTooltip(info(t), { direction: 'top', className: 'armada-info', opacity: 1 })
                    .addTo(lapisan);
                m.on('tooltipopen', function () {
                    if (layarKecil()) { m.closeTooltip(); tampilPanel(t); }
                    else aturArah(m);
                });
                m.on('click', function () { if (layarKecil()) tampilPanel(t); });
                marker[t.id] = m;
                titik.push([t.lat, t.lng]);
            });

            // Pusatkan hanya pertama kali, supaya geseran/zoom admin tidak hilang saat data diperbarui
            if (!sudahDipusatkan) {
                if (titik.length > 1) map.fitBounds(L.latLngBounds(titik), { padding: [60, 60], maxZoom: 14 });
                else if (titik.length === 1) map.setView(titik[0], 13);
                else map.setView([-0.5022, 117.1536], 8);
                sudahDipusatkan = true;
            }
        }

        function daftar() {
            var q = document.getElementById('cari-truk').value.trim().toLowerCase();
            var ul = document.getElementById('daftar-truk');
            var cocok = data.truk.filter(function (t) {
                return !q || t.plat.toLowerCase().indexOf(q) >= 0 || t.supir.toLowerCase().indexOf(q) >= 0;
            });
            document.getElementById('jumlah-truk').textContent = data.truk.length + ' truk';

            if (!data.truk.length) {
                ul.innerHTML = '<li class="py-6 text-center text-sm text-slate-500">Belum ada data truk.</li>';
                return;
            }

            ul.innerHTML = cocok.map(function (t) {
                var warna = t.lat === null || t.lama ? 'bg-slate-400' : (t.pesanan ? 'bg-[#15803d]' : 'bg-[#06316b]');
                return '<li><button type="button" data-id="' + t.id + '" class="flex w-full items-start gap-3 py-3 text-left hover:bg-slate-50">' +
                    '<span class="mt-1 h-3 w-3 shrink-0 rounded-full ' + warna + '"></span>' +
                    '<span class="min-w-0 flex-1">' +
                        '<span class="block font-bold text-pwb-navy">' + esc(t.plat) + ' <span class="font-normal text-slate-500">· ' + esc(t.supir) + '</span></span>' +
                        '<span class="block truncate text-xs text-slate-600">' + (t.pesanan ? esc(t.pesanan.status) + ' · ' + esc(t.pesanan.rute) : 'Tanpa pesanan') + '</span>' +
                        '<span class="block text-[11px] ' + (t.lat === null ? 'text-red-600' : 'text-slate-400') + '">' +
                            (t.lat === null ? 'Belum ada lokasi dari aplikasi' : 'Lokasi ' + esc(t.waktu_relatif)) + '</span>' +
                    '</span>' +
                '</button></li>';
            }).join('') || '<li class="py-6 text-center text-sm text-slate-500">Tidak ada yang cocok.</li>';
        }

        // Klik truk di daftar → peta menuju truk itu & tampilkan infonya
        document.getElementById('daftar-truk').addEventListener('click', function (e) {
            var b = e.target.closest('button[data-id]');
            if (!b) return;
            var m = marker[b.getAttribute('data-id')];
            if (!m) return;
            map.flyTo(m.getLatLng(), Math.max(map.getZoom(), 14), { duration: 0.6 });
            var t = data.truk.filter(function (x) { return String(x.id) === b.getAttribute('data-id'); })[0];
            setTimeout(function () { if (layarKecil()) tampilPanel(t); else m.openTooltip(); }, 650);
            if (window.innerWidth < 1280) document.getElementById('peta-armada').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        document.getElementById('cari-truk').addEventListener('input', daftar);

        gambar();
        daftar();
        setTimeout(function () { map.invalidateSize(); }, 200);

        // Perbarui posisi tiap 1 menit
        setInterval(function () {
            fetch(urlData, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (baru) {
                    if (!baru) return;
                    data = baru;
                    gambar();
                    daftar();
                    document.getElementById('jam-update').textContent = baru.diperbarui;
                })
                .catch(function () {});
        }, 60000);
    });
    </script>
@endpush
