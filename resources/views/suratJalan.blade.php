<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Surat Jalan {{ $p->no_resi }} - Putra Wijaya Berjaya</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { pwb: { navy: '#12285C', blue: '#2563EB', red: '#D7263D', sky: '#DCEAFB', bg: '#F4F7FC', line: '#DCE3F0' } },
                fontFamily: { sans: ['Inter', 'Arial', 'sans-serif'] }
            } }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @page { size: A4; margin: 12mm; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            #kertas { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: auto !important; }
        }
        #kertas { width: 794px; }
        #bingkai { transform-origin: top left; }
        table.garis td, table.garis th { border: 1px solid #cbd5e1; }
    </style>
</head>
<body class="bg-pwb-bg font-sans text-slate-800">

    {{-- Tombol (tidak ikut tercetak) --}}
    <div class="no-print sticky top-0 z-10 border-b border-pwb-line bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-2 px-4 py-3">
            <p class="text-sm font-bold text-pwb-navy"><i class="fa-solid fa-file-lines mr-1"></i> Surat Jalan {{ $p->no_resi }}</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $tracking }}" class="rounded-lg border border-pwb-line px-4 py-2.5 text-sm font-bold text-pwb-navy hover:bg-slate-50">
                    <i class="fa-solid fa-location-dot mr-1"></i> Lacak Truk
                </a>
                <button type="button" onclick="window.print()" class="rounded-lg bg-pwb-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-900">
                    <i class="fa-solid fa-print mr-1"></i> Cetak / Simpan PDF
                </button>
            </div>
        </div>
        @if ($admin)
            <div class="mx-auto max-w-4xl px-4 pb-3 text-xs text-slate-500">
                @if ($p->surat_jalan_wa_status)
                    <i class="fa-brands fa-whatsapp mr-1"></i> WhatsApp ke pelanggan: <b>{{ $p->surat_jalan_wa_status }}</b>
                    @if ($p->surat_jalan_wa_at) ({{ $p->surat_jalan_wa_at->locale('id')->translatedFormat('d M Y, H.i') }}) @endif
                @else
                    <i class="fa-brands fa-whatsapp mr-1"></i> Belum pernah dikirim lewat WhatsApp.
                @endif
            </div>
        @endif
    </div>

    {{-- Pesan --}}
    @if (session('success') || session('info') || $errors->any())
        <div class="no-print mx-auto mt-4 max-w-4xl px-4">
            <div class="rounded-lg px-4 py-3 text-sm font-semibold {{ $errors->any() ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }}">
                {{ $errors->any() ? $errors->first() : (session('success') ?? session('info')) }}
            </div>
        </div>
    @endif
    @if ($admin && $p->ttd_at)
        <form method="POST" action="{{ route('suratJalan.ttd.hapus', $p) }}" class="no-print mx-auto mt-3 max-w-4xl px-4 text-right"
              onsubmit="return confirm('Hapus tanda tangan penerima? Pelanggan perlu menandatangani ulang.')">
            @csrf @method('DELETE')
            <button class="text-xs font-bold text-red-600 hover:underline"><i class="fa-solid fa-eraser mr-1"></i>Hapus tanda tangan penerima</button>
        </form>
    @endif

    <div id="wadah" class="mx-auto my-6 max-w-4xl overflow-hidden px-4 print:m-0 print:p-0">
        <div id="bingkai">
            <div id="kertas" class="mx-auto bg-white p-10 shadow-sm">

                {{-- Judul + kop --}}
                <h1 class="text-center text-2xl font-extrabold tracking-[0.2em] text-pwb-navy">SURAT JALAN</h1>
                <header class="mt-4 flex items-center gap-4 border-b-[3px] border-pwb-navy pb-4">
                    <img src="{{ asset('images/pwb-logo.png') }}" onerror="this.src='{{ asset('images/logo-pwb.png') }}'" alt="PWB" class="h-14 w-auto">
                    <div>
                        <p class="text-lg font-extrabold leading-tight text-pwb-navy">PT PUTRA WIJAYA BERJAYA</p>
                        <p class="text-[11px] leading-snug text-slate-500">Jl. Raya Simpang Pasir RT 29 Samarinda, Kalimantan Timur</p>
                        <p class="text-[11px] leading-snug text-slate-500">WA +62 852-1881-5858 · putrawijaya.berjaya@gmail.com</p>
                    </div>
                </header>

                {{-- Kepada & keterangan --}}
                <section class="mt-5 grid grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="text-slate-500">Kepada Yth.</p>
                        <p class="text-base font-extrabold text-pwb-navy">{{ $p->pelanggan?->nama ?? '-' }}</p>
                        <p class="text-slate-600">{{ $p->pelanggan?->alamat }}</p>
                    </div>
                    <table class="h-fit w-full text-sm">
                        @foreach ([
                            'Tgl. Surat Jalan' => ($p->tanggal_kirim ?? now())->locale('id')->translatedFormat('d F Y'),
                            'No. Resi'         => $p->no_resi,
                            'Penjual'          => $p->penjual ?: '-',
                            'Sopir'            => $p->truck?->supir?->nama ?? '-',
                            'Plat Mobil'       => $p->truck?->plat ?? '-',
                        ] as $k => $v)
                            <tr>
                                <td class="w-32 py-0.5 align-top text-slate-500">{{ $k }}</td>
                                <td class="py-0.5 align-top font-semibold">: {{ $v }}</td>
                            </tr>
                        @endforeach
                    </table>
                </section>

                {{-- Barang --}}
                <table class="garis mt-6 w-full border-collapse text-sm">
                    <thead class="bg-pwb-sky/60 text-pwb-navy">
                        <tr>
                            <th class="w-12 px-3 py-2 text-center">No</th>
                            <th class="px-3 py-2 text-left">Nama Barang</th>
                            <th class="w-36 px-3 py-2 text-left">Kode Barang</th>
                            <th class="w-24 px-3 py-2 text-right">Qty</th>
                            <th class="w-28 px-3 py-2 text-left">Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($barang as $b)
                            <tr>
                                <td class="px-3 py-2.5 text-center">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2.5 font-semibold">{{ $b['nama'] }}</td>
                                <td class="px-3 py-2.5">{{ $b['kode'] ?? '' }}</td>
                                <td class="px-3 py-2.5 text-right">{{ \App\Support\SuratJalan::angka($b['qty'] ?? null) }}</td>
                                <td class="px-3 py-2.5">{{ $b['satuan'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Tanda tangan penerima (bisa online) --}}
                <section class="mt-10 flex justify-end text-center text-sm">
                    <div class="w-64">
                        <p class="text-slate-500">Penerima,</p>
                        <div class="flex h-24 items-end justify-center">
                            @if ($p->ttd_at)
                                <img src="{{ $p->ttd_gambar }}" alt="Tanda tangan {{ $p->ttd_nama }}" class="max-h-24 max-w-full">
                            @else
                                <button type="button" onclick="bukaTtd()" class="no-print mb-3 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-green-700">
                                    <i class="fa-solid fa-signature mr-1"></i> Tanda Tangani
                                </button>
                            @endif
                        </div>
                        <p class="border-t border-slate-400 pt-1 text-xs font-semibold">{{ $p->ttd_nama ?: '(.........................)' }}</p>
                        @if ($p->ttd_at)
                            <p class="mt-0.5 text-[10px] text-slate-500">Ditandatangani online {{ $p->ttd_at->locale('id')->translatedFormat('d M Y, H.i') }} WIB</p>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>

    @if ($p->buktiFoto->isNotEmpty())
        <section class="no-print mx-auto mb-10 max-w-4xl px-4">
            <h2 class="mb-3 font-bold text-pwb-navy"><i class="fa-solid fa-camera mr-1"></i> Foto Bukti Pengiriman</h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($p->buktiFoto as $f)
                    <a href="{{ $f->url() }}" target="_blank" class="block overflow-hidden rounded-lg border border-pwb-line bg-white">
                        <img src="{{ $f->url() }}" loading="lazy" alt="Foto bukti {{ $loop->iteration }}" class="aspect-[4/3] w-full object-cover">
                        <div class="px-2 py-1.5 text-[11px] leading-snug text-slate-600">
                            <p class="font-bold text-pwb-navy">{{ $f->waktuLabel() }}</p>
                            @if ($f->alamat)<p><i class="fa-solid fa-location-dot mr-0.5 text-red-500"></i>{{ $f->alamat }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @unless ($p->ttd_at)
        <div id="modal-ttd" class="no-print fixed inset-0 z-50 hidden items-end justify-center bg-black/50 p-3 sm:items-center">
            <form method="POST" action="{{ $urlTtd }}" onsubmit="return kirimTtd()" class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl">
                <h2 class="text-lg font-extrabold text-pwb-navy"><i class="fa-solid fa-signature mr-1"></i> Tanda Tangan Penerima</h2>
                <p class="mb-4 text-xs text-slate-500">Tanda tangani setelah barang diterima dan diperiksa.</p>

                <label for="nama-ttd" class="mb-1 block text-sm font-bold text-pwb-navy">Nama penerima</label>
                <input id="nama-ttd" name="nama" required maxlength="100" value="{{ old('nama') }}" placeholder="Nama lengkap"
                       class="w-full rounded-lg border border-pwb-line px-4 py-3 text-sm outline-none focus:border-pwb-blue">

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-sm font-bold text-pwb-navy">Tanda tangan</span>
                    <button type="button" onclick="ulangTtd()" class="text-xs font-bold text-pwb-blue"><i class="fa-solid fa-rotate-left mr-1"></i>Ulangi</button>
                </div>
                <canvas id="kanvas-ttd" class="mt-1 h-48 w-full touch-none rounded-lg border-2 border-dashed border-pwb-line bg-slate-50"></canvas>
                <p class="mt-1 text-center text-[11px] text-slate-400">Gores dengan jari / mouse di kotak di atas</p>
                <input type="hidden" name="ttd" id="isi-ttd">
                <p id="galat-ttd" class="mt-2 hidden text-xs font-semibold text-red-600">Silakan buat tanda tangan dulu.</p>

                <div class="mt-5 flex gap-2">
                    <button type="button" onclick="tutupTtd()" class="flex-1 rounded-lg border border-pwb-line px-4 py-3 text-sm font-bold text-pwb-navy">Batal</button>
                    <button type="submit" id="tombol-ttd" class="flex-1 rounded-lg bg-green-600 px-4 py-3 text-sm font-bold text-white hover:bg-green-700">Simpan Tanda Tangan</button>
                </div>
            </form>
        </div>

        <script>
            (function () {
                var modal = document.getElementById('modal-ttd');
                var kanvas = document.getElementById('kanvas-ttd');
                var ctx = kanvas.getContext('2d');
                var ada = false, gambar = false, akhir = null;

                function siapkan() {
                    var r = kanvas.getBoundingClientRect(), d = window.devicePixelRatio || 1;
                    kanvas.width = r.width * d; kanvas.height = r.height * d;
                    ctx.setTransform(d, 0, 0, d, 0, 0);
                    ctx.lineWidth = 2.5; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#12285C';
                    ada = false;
                }
                function titik(e) { var r = kanvas.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }

                kanvas.addEventListener('pointerdown', function (e) { gambar = true; akhir = titik(e); kanvas.setPointerCapture(e.pointerId); e.preventDefault(); });
                kanvas.addEventListener('pointermove', function (e) {
                    if (!gambar) return;
                    var t = titik(e);
                    ctx.beginPath(); ctx.moveTo(akhir.x, akhir.y); ctx.lineTo(t.x, t.y); ctx.stroke();
                    akhir = t; ada = true; e.preventDefault();
                });
                ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (n) { kanvas.addEventListener(n, function () { gambar = false; }); });

                window.bukaTtd = function () {
                    modal.classList.remove('hidden'); modal.classList.add('flex');
                    siapkan();
                    document.getElementById('nama-ttd').focus();
                };
                window.tutupTtd = function () { modal.classList.add('hidden'); modal.classList.remove('flex'); };
                window.ulangTtd = function () { siapkan(); };
                window.kirimTtd = function () {
                    if (!ada) { document.getElementById('galat-ttd').classList.remove('hidden'); return false; }
                    document.getElementById('isi-ttd').value = kanvas.toDataURL('image/png');
                    var b = document.getElementById('tombol-ttd'); b.disabled = true; b.textContent = 'Menyimpan…';
                    return true;
                };
                @if ($errors->any()) window.bukaTtd(); @endif
            })();
        </script>
    @endunless

    <script>
        // Tampilan HP: perkecil kertas A4 supaya muat di layar (cetak tetap ukuran penuh)
        (function () {
            var kertas = document.getElementById('kertas'), bingkai = document.getElementById('bingkai'), wadah = document.getElementById('wadah');
            function skala() {
                if (window.matchMedia('print').matches) return;
                var s = Math.min(1, (wadah.clientWidth - 32) / kertas.offsetWidth);
                bingkai.style.transform = s < 1 ? 'scale(' + s + ')' : '';
                bingkai.style.height = s < 1 ? (kertas.offsetHeight * s) + 'px' : '';
            }
            window.addEventListener('resize', skala);
            window.addEventListener('beforeprint', function () { bingkai.style.transform = ''; bingkai.style.height = ''; });
            window.addEventListener('afterprint', skala);
            skala();
        })();
    </script>
</body>
</html>
