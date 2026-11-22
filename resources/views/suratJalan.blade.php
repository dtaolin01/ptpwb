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

                {{-- Sopir & plat --}}
                <table class="mt-4 text-sm">
                    <tr><td class="w-32 py-0.5 text-slate-500">Sopir</td><td class="py-0.5 font-semibold">: {{ $p->truck?->supir?->nama ?? '-' }}</td></tr>
                    <tr><td class="w-32 py-0.5 text-slate-500">Plat Mobil</td><td class="py-0.5 font-semibold">: {{ $p->truck?->plat ?? '-' }}</td></tr>
                </table>

                {{-- Tanda tangan --}}
                <section class="mt-10 grid grid-cols-3 gap-8 text-center text-sm">
                    @foreach (['Hormat kami,' => 'PT Putra Wijaya Berjaya', 'Sopir,' => $p->truck?->supir?->nama, 'Penerima,' => null] as $atas => $bawah)
                        <div>
                            <p class="text-slate-500">{{ $atas }}</p>
                            <div class="h-20"></div>
                            <p class="border-t border-slate-400 pt-1 text-xs font-semibold">{{ $bawah ?: '(.........................)' }}</p>
                        </div>
                    @endforeach
                </section>
            </div>
        </div>
    </div>

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
