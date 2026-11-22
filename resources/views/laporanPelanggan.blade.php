<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laporan untuk Pelanggan {{ $p->no_resi }} - Putra Wijaya Berjaya</title>

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
    <style>
        /* Kertas selalu selebar A4 supaya PDF sama persis di HP maupun komputer.
           Di layar sempit, hanya tampilannya yang diperkecil (bingkai), kertasnya tidak. */
        #isi { width: 718px; }   /* = 190 mm: lebar A4 dikurangi margin 10 mm kiri-kanan */
        #bingkai { transform-origin: top left; }
        .hindari-potong { break-inside: avoid; page-break-inside: avoid; }
    </style>
</head>
<body class="bg-pwb-bg font-sans text-slate-800">

    {{-- ===== Panel admin (tidak ikut masuk PDF) ===== --}}
    <div class="sticky top-0 z-20 border-b border-pwb-line bg-white/95 backdrop-blur">
        <div class="mx-auto max-w-4xl px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                @if (empty($dariAplikasi))
                    <a href="{{ route('trackingAdmin', ['resi' => $p->no_resi]) }}" class="text-sm font-bold text-pwb-navy hover:underline">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Tracking
                    </a>
                @else
                    <span class="text-sm font-bold text-pwb-navy"><i class="fa-solid fa-mobile-screen mr-1"></i> Dibuka dari PWB Admin</span>
                @endif
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="btn-unduh" class="tombol-aksi rounded-lg bg-pwb-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-900 disabled:opacity-50">
                        <i class="fa-solid fa-file-arrow-down mr-1.5"></i>Unduh PDF
                    </button>
                    <button type="button" id="btn-email" class="tombol-aksi rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800 disabled:opacity-50">
                        <i class="fa-solid fa-envelope mr-1.5"></i>Kirim Email
                    </button>
                    <button type="button" id="btn-wa" class="tombol-aksi rounded-lg bg-green-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-50">
                        <i class="fa-brands fa-whatsapp mr-1.5"></i>Kirim WhatsApp
                    </button>
                </div>
            </div>

            <p class="mt-2 text-xs text-slate-500">
                <i class="fa-solid fa-lock mr-1"></i>Halaman ini hanya bisa dibuka admin{{ empty($dariAplikasi) ? '' : ' (link sementara, berlaku 2 jam)' }}. Pelanggan menerima file PDF-nya saja.
                <span id="info-kirim">
                    @if ($p->laporan_dikirim_at)
                        · Terakhir dikirim <b>{{ $p->laporan_dikirim_at->locale('id')->translatedFormat('d M Y, H.i') }}</b> ke <b>{{ $p->laporan_dikirim_ke }}</b>
                    @else
                        · Belum pernah dikirim ke pelanggan.
                    @endif
                </span>
            </p>
            <div id="status" class="mt-2 hidden rounded-lg px-3 py-2 text-sm"></div>
        </div>
    </div>

    @unless ($lap['selesai_status'])
        <div class="mx-auto mt-4 max-w-4xl px-4">
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                Pengiriman ini belum selesai (status: <b>{{ $p->status_label }}</b>). Sebaiknya laporan dikirim setelah supir menekan "Selesaikan Pesanan".
            </div>
        </div>
    @endunless

    {{-- ===== Kertas laporan (inilah yang menjadi PDF) ===== --}}
    <div id="wadah" class="mx-auto my-6 max-w-4xl overflow-hidden px-4">
        <div id="bingkai">
            <div id="kertas" class="mx-auto w-max bg-white p-9 text-slate-800 shadow-sm">
            <div id="isi" class="bg-white">
                @php $r = $lap['ringkasan']; @endphp

                {{-- Kop --}}
                <header class="flex items-start justify-between gap-4 border-b-2 border-pwb-navy pb-4">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('images/pwb-logo.png') }}" alt="PWB" class="h-14 w-auto">
                        <div>
                            <p class="text-lg font-extrabold leading-tight text-pwb-navy">PT PUTRA WIJAYA BERJAYA</p>
                            <p class="text-[11px] leading-snug text-slate-500">Jl. Raya Simpang Pasir RT 29 Samarinda, Kalimantan Timur</p>
                            <p class="text-[11px] leading-snug text-slate-500">WA +62 852-1881-5858 · putrawijaya.berjaya@gmail.com</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <h1 class="text-xl font-extrabold text-pwb-navy">LAPORAN PENGIRIMAN</h1>
                        <p class="mt-1 text-sm font-bold">No. Resi: {{ $p->no_resi }}</p>
                        <p class="text-[11px] text-slate-500">Tanggal: {{ now()->locale('id')->translatedFormat('d F Y') }}</p>
                    </div>
                </header>

                {{-- Kepada --}}
                <section class="mt-4 flex items-start justify-between gap-6 text-sm">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Kepada Yth.</p>
                        <p class="font-extrabold text-pwb-navy">{{ $p->pelanggan?->nama ?? '-' }}</p>
                        <p class="max-w-md text-xs text-slate-600">{{ $p->pelanggan?->alamat }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Status</p>
                        <p class="inline-block rounded-full px-3 py-0.5 text-xs font-bold {{ $lap['selesai_status'] ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $lap['selesai_status'] ? 'Selesai — barang telah diterima' : $p->status_label }}
                        </p>
                    </div>
                </section>

                {{-- Ringkasan --}}
                <section class="hindari-potong mt-4 grid grid-cols-4 gap-3">
                    @foreach ([
                        ['Berangkat', $r['berangkat'] ?? '-', 'fa-play'],
                        ['Tiba di tujuan', $r['tiba'] ?? '-', 'fa-flag-checkered'],
                        ['Lama pengiriman', $r['total'] ?? '-', 'fa-clock'],
                        ['Ketepatan waktu', $r['ketepatan'][0] ?? '-', 'fa-circle-check'],
                    ] as $i => [$label, $nilai, $ikon])
                        <div class="rounded-lg border border-pwb-line p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500"><i class="fa-solid {{ $ikon }} mr-1"></i>{{ $label }}</p>
                            <p class="mt-1 text-sm font-extrabold {{ $i === 3 && $r['ketepatan'] ? ($r['ketepatan'][1] === 'green' ? 'text-green-700' : 'text-red-600') : 'text-pwb-navy' }}">{{ $nilai }}</p>
                        </div>
                    @endforeach
                </section>

                {{-- Data pengiriman & armada --}}
                <section class="hindari-potong mt-4 grid grid-cols-2 gap-4">
                    @foreach (['Data Pengiriman' => $lap['data'], 'Armada' => $lap['armada']] as $judul => $isi)
                        <div class="rounded-lg border border-pwb-line">
                            <h2 class="rounded-t-lg bg-pwb-sky/60 px-4 py-2 text-sm font-bold text-pwb-navy">{{ $judul }}</h2>
                            <dl class="divide-y divide-pwb-line px-4 text-xs">
                                @foreach ($isi as $k => $v)
                                    @continue($k === 'No. Resi' || $k === 'Status')
                                    <div class="flex gap-3 py-1.5">
                                        <dt class="w-28 shrink-0 text-slate-500">{{ $k }}</dt>
                                        <dd class="font-semibold text-slate-800">{{ $v }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endforeach
                </section>

                {{-- Waktu per tahap --}}
                <section class="hindari-potong mt-4">
                    <h2 class="mb-2 text-sm font-bold text-pwb-navy"><i class="fa-solid fa-stopwatch mr-2"></i>Waktu per Tahap</h2>
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 font-bold text-pwb-navy">
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
                                    <td class="px-3 py-1.5 font-semibold">{{ $t['nama'] }}</td>
                                    <td class="px-3 py-1.5">{{ $t['mulai'] ?? '-' }}</td>
                                    <td class="px-3 py-1.5">{{ $t['selesai'] ?? '-' }}</td>
                                    <td class="px-3 py-1.5 text-right font-bold text-pwb-navy">{{ $t['durasi'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-slate-50">
                                <td class="px-3 py-1.5 font-extrabold" colspan="3">Total</td>
                                <td class="px-3 py-1.5 text-right font-extrabold text-pwb-navy">{{ $r['total'] ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                {{-- Peta (gambar statis supaya ikut masuk PDF) --}}
                <section id="bagian-peta" class="hindari-potong mt-4">
                    <h2 class="mb-2 text-sm font-bold text-pwb-navy"><i class="fa-solid fa-map-location-dot mr-2"></i>Rute Pengiriman</h2>
                    <div class="overflow-hidden rounded-lg border border-pwb-line bg-slate-100">
                        <img id="gambar-peta" alt="Peta rute" class="block w-full" style="height:250px; object-fit:cover;">
                    </div>
                    <p class="mt-1 flex gap-4 text-[10px] text-slate-500">
                        <span><span class="inline-block h-2.5 w-2.5 rounded-full" style="background:#1553B5"></span> A = Asal</span>
                        @if ($p->lewat_pabrik)<span><span class="inline-block h-2.5 w-2.5 rounded-full" style="background:#F0CC1B"></span> Pabrik (muat)</span>@endif
                        <span><span class="inline-block h-2.5 w-2.5 rounded-full" style="background:#CC2724"></span> B = Tujuan</span>
                    </p>
                </section>

                {{-- Riwayat --}}
                <section class="hindari-potong mt-4">
                    <h2 class="mb-2 text-sm font-bold text-pwb-navy"><i class="fa-solid fa-route mr-2"></i>Riwayat Perjalanan</h2>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                        @forelse ($lap['langkah'] as $l)
                            <div class="flex gap-2">
                                <span class="mt-1 h-3 w-3 shrink-0 rounded-full {{ $l['selesai'] ? 'bg-pwb-navy' : 'bg-slate-300' }}"></span>
                                <div>
                                    <p class="text-xs font-bold {{ $l['selesai'] ? 'text-slate-800' : 'text-slate-400' }}">{{ $l['judul'] }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $l['waktu'] ?? 'Belum dilewati' }}@if ($l['lokasi']) · {{ $l['lokasi'] }}@endif</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">Belum ada riwayat perjalanan.</p>
                        @endforelse
                    </div>
                </section>

                {{-- Tanda tangan --}}
                <section class="hindari-potong mt-8 grid grid-cols-2 gap-16 text-center text-sm">
                    <div>
                        <p class="text-xs text-slate-500">Hormat kami,</p>
                        <p class="font-bold text-pwb-navy">PT Putra Wijaya Berjaya</p>
                        <div class="h-14"></div>
                        <p class="border-t border-slate-400 pt-1 text-xs text-slate-600">(.................................)</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Diterima oleh,</p>
                        <p class="font-bold text-pwb-navy">{{ $p->pelanggan?->nama ?? 'Penerima' }}</p>
                        <div class="h-14"></div>
                        <p class="border-t border-slate-400 pt-1 text-xs text-slate-600">(.................................)</p>
                    </div>
                </section>

                <footer class="mt-6 border-t border-pwb-line pt-2 text-center text-[10px] text-slate-400">
                    Terima kasih telah mempercayakan pengiriman Anda kepada PT Putra Wijaya Berjaya.
                    Waktu pada laporan ini dicatat otomatis dari sistem pelacakan armada kami.
                </footer>
            </div>
            </div>
        </div>
    </div>

    {{-- ===== Jendela kirim email ===== --}}
    <div id="modal-email" class="fixed inset-0 z-30 hidden items-center justify-center bg-black/40 p-4">
        <form id="form-email" class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
            <h2 class="text-lg font-bold text-pwb-navy"><i class="fa-solid fa-envelope mr-2"></i>Kirim Laporan lewat Email</h2>
            <p class="mt-1 text-xs text-slate-500">PDF laporan akan dilampirkan otomatis.</p>

            <label class="mt-4 block text-sm font-bold text-slate-700" for="input-email">Email pelanggan</label>
            <input id="input-email" type="email" required value="{{ $p->pelanggan?->email }}"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-pwb-blue focus:outline-none" placeholder="nama@perusahaan.com">

            <label class="mt-3 block text-sm font-bold text-slate-700" for="input-pesan">Pesan (opsional)</label>
            <textarea id="input-pesan" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-pwb-blue focus:outline-none"
                      placeholder="Kosongkan untuk memakai pesan standar."></textarea>

            <div class="mt-4 flex justify-end gap-2">
                <button type="button" id="btn-batal" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" id="btn-kirim-email" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800 disabled:opacity-50">
                    <i class="fa-solid fa-paper-plane mr-1.5"></i>Kirim
                </button>
            </div>
        </form>
    </div>

    <script type="application/json" id="data-peta">@json($lap['peta'])</script>
    <script src="{{ asset('vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>
    <script>
    (function () {
        var URL_KIRIM = @json($urlKirim ?? route('trackingAdmin.kirimLaporan', $p));
        var NAMA_PDF = @json($namaPdf);
        var NOMOR_WA = @json($wa);
        var RESI = @json($p->no_resi);
        var PELANGGAN = @json($p->pelanggan?->nama ?? '');
        var CSRF = document.querySelector('meta[name="csrf-token"]').content;

        var kertas = document.getElementById('kertas');
        var isi = document.getElementById('isi');
        var bingkai = document.getElementById('bingkai');
        var wadah = document.getElementById('wadah');
        var kotakStatus = document.getElementById('status');

        // ---------- Tampilan di HP: perkecil kertas agar muat di layar ----------
        function sesuaikanSkala() {
            var lebar = wadah.clientWidth - 32;
            var s = Math.min(1, lebar / kertas.offsetWidth);
            bingkai.style.transform = s < 1 ? 'scale(' + s + ')' : '';
            bingkai.style.height = s < 1 ? (kertas.offsetHeight * s) + 'px' : '';
        }
        window.addEventListener('resize', sesuaikanSkala);

        function tampilStatus(teks, jenis) {
            var warna = { ok: 'bg-green-50 text-green-800 border border-green-200', gagal: 'bg-red-50 text-red-800 border border-red-200', info: 'bg-blue-50 text-blue-800 border border-blue-200' };
            kotakStatus.className = 'mt-2 rounded-lg px-3 py-2 text-sm ' + (warna[jenis] || warna.info);
            kotakStatus.innerHTML = teks;
        }

        // ---------- Peta statis: ubin OpenStreetMap + rute digambar di canvas ----------
        function proyeksi(lat, lng, z) {
            var n = 256 * Math.pow(2, z), r = lat * Math.PI / 180;
            return [(lng + 180) / 360 * n, (1 - Math.log(Math.tan(r) + 1 / Math.cos(r)) / Math.PI) / 2 * n];
        }

        function muatUbin(src) {
            return new Promise(function (ok) {
                var img = new Image(), selesai = false;
                img.crossOrigin = 'anonymous';
                img.onload = function () { if (!selesai) { selesai = true; ok(img); } };
                img.onerror = function () { if (!selesai) { selesai = true; ok(null); } };
                setTimeout(function () { if (!selesai) { selesai = true; ok(null); } }, 8000);
                img.src = src;
            });
        }

        function gambarPeta(d) {
            var W = 718, H = 250, PAD = 34, S = 2;
            var titik = [];
            (d.route || []).forEach(function (t) { titik.push(t); });
            (d.pins || []).forEach(function (p) { titik.push([p.lat, p.lng]); });
            if (!titik.length) return Promise.resolve(null);

            var z = 15, a, b;
            for (; z > 3; z--) {
                a = [Infinity, Infinity]; b = [-Infinity, -Infinity];
                titik.forEach(function (t) {
                    var q = proyeksi(t[0], t[1], z);
                    a = [Math.min(a[0], q[0]), Math.min(a[1], q[1])];
                    b = [Math.max(b[0], q[0]), Math.max(b[1], q[1])];
                });
                if (b[0] - a[0] <= W - PAD * 2 && b[1] - a[1] <= H - PAD * 2) break;
            }
            var ox = (a[0] + b[0]) / 2 - W / 2, oy = (a[1] + b[1]) / 2 - H / 2;
            var jumlah = Math.pow(2, z);

            var daftar = [];
            for (var tx = Math.floor(ox / 256); tx <= Math.floor((ox + W) / 256); tx++) {
                for (var ty = Math.floor(oy / 256); ty <= Math.floor((oy + H) / 256); ty++) {
                    if (ty < 0 || ty >= jumlah) continue;
                    var x = ((tx % jumlah) + jumlah) % jumlah;
                    daftar.push({ x: tx * 256 - ox, y: ty * 256 - oy, src: 'https://tile.openstreetmap.org/' + z + '/' + x + '/' + ty + '.png' });
                }
            }

            return Promise.all(daftar.map(function (u) { return muatUbin(u.src); })).then(function (ubin) {
                function lukis(pakaiUbin) {
                    var c = document.createElement('canvas');
                    c.width = W * S; c.height = H * S;
                    var g = c.getContext('2d');
                    g.scale(S, S);
                    g.fillStyle = '#e8edf3'; g.fillRect(0, 0, W, H);
                    if (pakaiUbin) ubin.forEach(function (img, i) { if (img) g.drawImage(img, daftar[i].x, daftar[i].y, 256, 256); });

                    function xy(t) { var q = proyeksi(t[0], t[1], z); return [q[0] - ox, q[1] - oy]; }

                    if ((d.route || []).length > 1) {
                        [['#ffffff', 8], ['#1553B5', 4.5]].forEach(function (gaya) {
                            g.beginPath();
                            d.route.forEach(function (t, i) { var p = xy(t); i ? g.lineTo(p[0], p[1]) : g.moveTo(p[0], p[1]); });
                            g.strokeStyle = gaya[0]; g.lineWidth = gaya[1]; g.lineJoin = g.lineCap = 'round'; g.stroke();
                        });
                    }

                    var warna = { origin: ['#1553B5', 'A', '#fff'], destination: ['#CC2724', 'B', '#fff'], stop: ['#F0CC1B', '', '#06316B'] };
                    (d.pins || []).forEach(function (p) {
                        var w = warna[p.type] || warna.stop, q = xy([p.lat, p.lng]);
                        g.beginPath(); g.arc(q[0], q[1], 12, 0, Math.PI * 2);
                        g.fillStyle = w[0]; g.fill(); g.lineWidth = 3; g.strokeStyle = '#ffffff'; g.stroke();
                        g.fillStyle = w[2]; g.font = 'bold 12px Arial'; g.textAlign = 'center'; g.textBaseline = 'middle';
                        if (w[1]) g.fillText(w[1], q[0], q[1] + 0.5);
                        else { g.beginPath(); g.arc(q[0], q[1], 3.5, 0, Math.PI * 2); g.fill(); }
                    });

                    var atr = '© OpenStreetMap contributors';
                    g.font = '9px Arial'; g.textAlign = 'right'; g.textBaseline = 'bottom';
                    var lw = g.measureText(atr).width + 8;
                    g.fillStyle = 'rgba(255,255,255,.85)'; g.fillRect(W - lw, H - 14, lw, 14);
                    g.fillStyle = '#475569'; g.fillText(atr, W - 4, H - 2);
                    return c.toDataURL('image/jpeg', 0.9);
                }
                try { return lukis(true); } catch (e) { return lukis(false); } // ubin diblokir CORS → tanpa ubin
            });
        }

        var petaSiap = gambarPeta(JSON.parse(document.getElementById('data-peta').textContent)).then(function (url) {
            if (url) document.getElementById('gambar-peta').src = url;
            else document.getElementById('bagian-peta').remove();
        }).catch(function () {
            document.getElementById('bagian-peta').remove();
        }).then(function () {
            return new Promise(function (ok) { requestAnimationFrame(function () { sesuaikanSkala(); ok(); }); });
        });
        sesuaikanSkala();

        // ---------- PDF ----------
        function opsiPdf() {
            return {
                margin: [10, 10, 10, 10],
                filename: NAMA_PDF,
                image: { type: 'jpeg', quality: 0.95 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', scrollX: 0, scrollY: 0 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'], avoid: '.hindari-potong' }
            };
        }

        function denganKertasPolos(kerja) {
            // Yang dipotret hanya #isi (tanpa bayangan/padding layar); skala tampilan HP tidak berpengaruh
            return petaSiap.then(kerja);
        }

        function buatBlob() {
            return denganKertasPolos(function () { return html2pdf().set(opsiPdf()).from(isi).outputPdf('blob'); });
        }

        function unduh() {
            return denganKertasPolos(function () { return html2pdf().set(opsiPdf()).from(isi).save(); });
        }

        var tombol = document.querySelectorAll('.tombol-aksi');
        function sibuk(ya) { tombol.forEach(function (b) { b.disabled = ya; }); }

        function catatWa() {
            var f = new FormData(); f.append('via', 'whatsapp');
            return fetch(URL_KIRIM, { method: 'POST', body: f, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
                .then(function () { document.getElementById('info-kirim').innerHTML = '· Terakhir dikirim <b>baru saja</b> lewat <b>WhatsApp</b>'; })
                .catch(function () {});
        }

        document.getElementById('btn-unduh').addEventListener('click', function () {
            sibuk(true); tampilStatus('<i class="fa-solid fa-spinner fa-spin mr-1"></i>Membuat PDF…', 'info');
            unduh().then(function () { tampilStatus('PDF sudah diunduh: <b>' + NAMA_PDF + '</b>', 'ok'); })
                .catch(function (e) { tampilStatus('Gagal membuat PDF: ' + e, 'gagal'); })
                .finally(function () { sibuk(false); });
        });

        // WhatsApp: di HP langsung bagikan file PDF ke WhatsApp; di komputer unduh PDF lalu buka chat WhatsApp pelanggan.
        document.getElementById('btn-wa').addEventListener('click', function () {
            var teks = 'Yth. ' + (PELANGGAN || 'Bapak/Ibu') + ', berikut laporan pengiriman No. Resi ' + RESI + ' dari PT Putra Wijaya Berjaya. Terima kasih.';
            // Jendela WA dibuka sekarang (saat klik) supaya tidak diblokir browser
            var bisaBagikanFile = !!(navigator.canShare && window.File && navigator.canShare({ files: [new File([''], 'x.pdf', { type: 'application/pdf' })] }));
            var jendela = bisaBagikanFile ? null : window.open('about:blank', '_blank');

            sibuk(true); tampilStatus('<i class="fa-solid fa-spinner fa-spin mr-1"></i>Menyiapkan PDF…', 'info');
            buatBlob().then(function (blob) {
                var file = new File([blob], NAMA_PDF, { type: 'application/pdf' });
                if (bisaBagikanFile) {
                    return navigator.share({ files: [file], title: 'Laporan ' + RESI, text: teks }).then(function () {
                        tampilStatus('PDF dibagikan. Pastikan sudah terkirim di WhatsApp.', 'ok');
                        return catatWa();
                    }, function (e) {
                        if (e && e.name === 'AbortError') tampilStatus('Berbagi dibatalkan.', 'info');
                        else throw e;
                    });
                }
                // Komputer: unduh PDF, lalu buka WhatsApp ke nomor pelanggan
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob); a.download = NAMA_PDF;
                document.body.appendChild(a); a.click(); a.remove();
                var urlWa = 'https://wa.me/' + (NOMOR_WA || '') + '?text=' + encodeURIComponent(teks);
                if (jendela) jendela.location = urlWa; else window.open(urlWa, '_blank');
                tampilStatus('PDF sudah diunduh (<b>' + NAMA_PDF + '</b>). Di WhatsApp yang terbuka, lampirkan file tersebut lalu kirim.' +
                    (NOMOR_WA ? '' : ' <br><b>Nomor WhatsApp pelanggan belum diisi</b> di data Pelanggan, jadi pilih kontaknya secara manual.'), 'ok');
                return catatWa();
            }).catch(function (e) {
                if (jendela) jendela.close();
                tampilStatus('Gagal menyiapkan PDF: ' + e, 'gagal');
            }).finally(function () { sibuk(false); });
        });

        // Email
        var modal = document.getElementById('modal-email');
        function bukaModal(ya) { modal.classList.toggle('hidden', !ya); modal.classList.toggle('flex', ya); }
        document.getElementById('btn-email').addEventListener('click', function () { bukaModal(true); document.getElementById('input-email').focus(); });
        document.getElementById('btn-batal').addEventListener('click', function () { bukaModal(false); });

        document.getElementById('form-email').addEventListener('submit', function (ev) {
            ev.preventDefault();
            var email = document.getElementById('input-email').value.trim();
            var pesan = document.getElementById('input-pesan').value.trim();
            var btn = document.getElementById('btn-kirim-email');
            btn.disabled = true; sibuk(true);
            bukaModal(false);
            tampilStatus('<i class="fa-solid fa-spinner fa-spin mr-1"></i>Membuat PDF dan mengirim ke ' + email + '…', 'info');

            buatBlob().then(function (blob) {
                var f = new FormData();
                f.append('via', 'email'); f.append('email', email); f.append('pesan', pesan);
                f.append('pdf', blob, NAMA_PDF);
                return fetch(URL_KIRIM, { method: 'POST', body: f, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (j) {
                    if (res.ok && j.ok) {
                        tampilStatus('<i class="fa-solid fa-circle-check mr-1"></i>' + j.message, 'ok');
                        document.getElementById('info-kirim').innerHTML = '· Terakhir dikirim <b>baru saja</b> ke <b>' + email + '</b>';
                        return;
                    }
                    var pesanGagal = j.message || 'Gagal mengirim (kode ' + res.status + ').';
                    if (j.errors) pesanGagal = Object.values(j.errors).map(function (x) { return x[0]; }).join('<br>');
                    tampilStatus(pesanGagal, 'gagal');
                });
            }).catch(function (e) {
                tampilStatus('Gagal mengirim: ' + e, 'gagal');
            }).finally(function () { btn.disabled = false; sibuk(false); });
        });
    })();
    </script>
</body>
</html>
