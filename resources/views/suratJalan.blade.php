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

    <div id="wadah" class="mx-auto my-6 max-w-4xl overflow-hidden px-4 print:m-0 print:p-0">
        <div id="bingkai">
            <div id="kertas" class="mx-auto bg-white p-10 shadow-sm">

                {{-- Kop --}}
                <header class="flex items-start justify-between gap-4 border-b-[3px] border-pwb-navy pb-4">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('images/pwb-logo.png') }}" onerror="this.src='{{ asset('images/logo-pwb.png') }}'" alt="PWB" class="h-14 w-auto">
                        <div>
                            <p class="text-lg font-extrabold leading-tight text-pwb-navy">PT PUTRA WIJAYA BERJAYA</p>
                            <p class="text-[11px] leading-snug text-slate-600">Logistics &amp; Distribution</p>
                            <p class="text-[11px] leading-snug text-slate-500">Jl. Raya Simpang Pasir RT 29 Samarinda, Kalimantan Timur</p>
                            <p class="text-[11px] leading-snug text-slate-500">WA +62 852-1881-5858 · putrawijaya.berjaya@gmail.com</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <h1 class="text-2xl font-extrabold tracking-wide text-pwb-navy">SURAT JALAN</h1>
                        <p class="mt-1 text-sm font-bold">No. {{ $nomor }}</p>
                        <p class="text-xs text-slate-500">Tanggal: {{ ($p->tanggal_kirim ?? now())->locale('id')->translatedFormat('d F Y') }}</p>
                    </div>
                </header>

                {{-- Kepada & pengiriman --}}
                <section class="mt-5 grid grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Kepada Yth.</p>
                        <p class="text-base font-extrabold text-pwb-navy">{{ $p->pelanggan?->nama ?? '-' }}</p>
                        @if ($p->pelanggan?->kontak)<p>u.p. {{ $p->pelanggan->kontak }}</p>@endif
                        <p class="text-slate-600">{{ $p->pelanggan?->alamat }}</p>
                        @if ($p->pelanggan?->telepon)<p class="text-slate-600">Telp/WA: {{ $p->pelanggan->telepon }}</p>@endif
                    </div>
                    <div>
                        <table class="w-full text-sm">
                            @foreach ([
                                'No. Resi'      => $p->no_resi,
                                'Tanggal kirim' => $p->tanggal_kirim?->locale('id')->translatedFormat('d F Y') ?? '-',
                                'Estimasi tiba' => $p->estimasi_tiba?->locale('id')->translatedFormat('d F Y') ?? '-',
                                'Asal'          => $p->asal ?: '-',
                                'Tujuan'        => $p->tujuan ?: '-',
                                'No. Polisi'    => $p->truck?->plat ?? '-',
                                'Jenis truk'    => $p->truck?->jenis ?? '-',
                                'Pengemudi'     => $p->truck?->supir?->nama ?? '-',
                            ] as $k => $v)
                                <tr>
                                    <td class="w-32 py-0.5 align-top text-slate-500">{{ $k }}</td>
                                    <td class="py-0.5 align-top font-semibold">: {{ $v }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </section>

                {{-- Barang --}}
                <section class="mt-6">
                    <p class="mb-2 text-sm">Bersama ini kami kirimkan barang-barang sebagai berikut:</p>
                    <table class="garis w-full border-collapse text-sm">
                        <thead class="bg-pwb-sky/60 text-pwb-navy">
                            <tr>
                                <th class="w-12 px-3 py-2 text-center">No</th>
                                <th class="px-3 py-2 text-left">Nama / Jenis Barang</th>
                                <th class="w-40 px-3 py-2 text-left">Jumlah</th>
                                <th class="w-48 px-3 py-2 text-left">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-3 py-3 text-center align-top">1</td>
                                <td class="px-3 py-3 align-top font-semibold">{{ $p->muatan }}</td>
                                <td class="px-3 py-3 align-top">&nbsp;</td>
                                <td class="px-3 py-3 align-top">{{ $p->lewat_pabrik ? 'Muat di pabrik' : 'Dalam kota' }}</td>
                            </tr>
                            @for ($i = 2; $i <= 4; $i++)
                                <tr><td class="px-3 py-3 text-center text-slate-300">{{ $i }}</td><td></td><td></td><td></td></tr>
                            @endfor
                        </tbody>
                    </table>
                    <p class="mt-2 text-[11px] text-slate-500">
                        Mohon barang diperiksa saat diterima. Kekurangan / kerusakan harap dicatat pada surat jalan ini sebelum ditandatangani.
                    </p>
                </section>

                {{-- Tanda tangan + QR --}}
                <section class="mt-8 grid grid-cols-4 gap-4 text-center text-sm">
                    @foreach (['Hormat kami,' => 'PT Putra Wijaya Berjaya', 'Pengemudi,' => $p->truck?->supir?->nama ?? '', 'Diterima oleh,' => 'Nama, tanggal & cap'] as $atas => $bawah)
                        <div>
                            <p class="text-xs text-slate-500">{{ $atas }}</p>
                            <div class="h-20"></div>
                            <p class="border-t border-slate-400 pt-1 text-xs font-semibold">{{ $bawah ?: '(.........................)' }}</p>
                        </div>
                    @endforeach
                    <div class="flex flex-col items-center">
                        <div id="qr" class="h-[96px] w-[96px]"></div>
                        <p class="mt-1 text-[10px] leading-tight text-slate-500">Pindai untuk<br>lacak pengiriman</p>
                    </div>
                </section>

                <footer class="mt-8 border-t border-pwb-line pt-2 text-center text-[10px] text-slate-400">
                    Surat jalan ini dibuat otomatis oleh sistem PT Putra Wijaya Berjaya · {{ $nomor }} · Lacak: {{ $tracking }}
                </footer>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // QR code ke halaman tracking
        try {
            new QRCode(document.getElementById('qr'), { text: @json($tracking), width: 96, height: 96, correctLevel: QRCode.CorrectLevel.M });
        } catch (e) {}

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
