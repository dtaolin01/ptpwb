<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('page_title', 'Admin') - Putra Wijaya Berjaya</title>

    {{-- Tailwind via CDN (tanpa build). Kalau nanti pakai Vite, config warna pindah ke tailwind.config.js --}}
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .hp-isi { display: contents; }   /* laptop: tidak mengubah apa pun */

        /* ===================== Tampilan HP (layar < 768px) ===================== */
        @media (max-width: 767px) {
            /* Huruf lebih besar & mudah dibaca */
            body { font-size: 16px; }
            .text-\[10px\] { font-size: 12px !important; }
            .text-\[11px\] { font-size: 13px !important; }
            .text-xs { font-size: 13.5px; line-height: 1.4; }
            .text-sm { font-size: 15.5px; line-height: 1.5; }
            .text-base { font-size: 17px; }

            /* Kolom isian 16px: HP tidak zoom otomatis saat mengetik */
            input:not([type=checkbox]):not([type=radio]), select, textarea { font-size: 16px !important; }

            /* Tombol ikon (ubah/hapus) lebih besar supaya mudah diketuk */
            .h-8.w-8 { height: 2.5rem; width: 2.5rem; }

            /* Tabel → kartu: setiap baris jadi satu kartu, label kolom di kiri */
            table.tabel-hp, table.tabel-hp tbody { display: block; width: 100%; min-width: 0 !important; }
            table.tabel-hp thead { display: none; }
            table.tabel-hp tbody > tr {
                display: block; background: #fff; border: 1px solid #DCE3F0 !important;
                border-radius: 14px; padding: 4px 16px; margin-bottom: 12px;
                box-shadow: 0 1px 3px rgba(18, 40, 92, .06);
            }
            table.tabel-hp tbody > tr > td {
                display: flex; align-items: center; justify-content: space-between; gap: 14px;
                padding: 11px 0 !important; text-align: right; max-width: none !important;
                border-bottom: 1px dashed #E5EAF3;
            }
            table.tabel-hp tbody > tr > td:last-child { border-bottom: 0; }
            table.tabel-hp tbody > tr > td::before {
                content: attr(data-label); flex-shrink: 0; max-width: 42%;
                text-align: left; font-weight: 700; color: #12285C; font-size: 14px;
            }
            table.tabel-hp tbody > tr > td:not([data-label])::before,
            table.tabel-hp tbody > tr > td[data-label=""]::before { content: none; }
            table.tabel-hp tbody > tr > td[colspan] { justify-content: center; text-align: center; }
            table.tabel-hp tbody > tr > td.hp-sembunyi { display: none; }
            table.tabel-hp tbody > tr > td > .hp-isi { display: block; min-width: 0; text-align: right; }
            table.tabel-hp tbody > tr > td > .hp-isi > * { display: block; margin-left: auto; }
            table.tabel-hp tbody > tr > td > .hp-isi > br { display: none; }
            table.tabel-hp tbody > tr > td.hp-judul > .hp-isi { text-align: left; }
            /* kolom foto: gambar di atas kartu, lebih besar, tanpa label */
            table.tabel-hp tbody > tr > td:has(img) { justify-content: center; }
            table.tabel-hp tbody > tr > td:has(img)::before { content: none; }
            table.tabel-hp tbody > tr > td img { height: 110px; width: auto; max-width: 100%; object-fit: contain; }
            /* baris pertama yang terlihat (mis. nama/resi) jadi judul kartu */
            table.tabel-hp tbody > tr > td.hp-judul { font-size: 17px; font-weight: 800; color: #12285C; }
            table.tabel-hp tbody > tr > td.hp-judul::before { content: none; }
            .overflow-x-auto:has(> table.tabel-hp) { background: transparent; overflow: visible; }

            /* Tabel berisi isian (mis. Status Perjalanan): tetap tabel, geser ke samping */
            .overflow-x-auto > table:not(.tabel-hp) { min-width: 680px; }
            .overflow-x-auto { -webkit-overflow-scrolling: touch; }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-pwb-bg font-sans text-slate-800">
<div class="flex min-h-screen">

    @include('partialsadmin.sidebar')

    {{-- Latar gelap saat menu dibuka di HP --}}
    <div id="sidebar-backdrop" class="fixed inset-0 z-[1090] hidden bg-black/40 lg:hidden"></div>

    <main class="min-w-0 flex-1">
        {{-- Banner atas: tombol menu (HP) + admin + tombol keluar + judul halaman --}}
        <header class="bg-pwb-sky px-4 pb-5 pt-3 sm:px-8 sm:pb-6 sm:pt-4">
            <div class="flex items-center justify-between gap-3 text-sm font-semibold text-pwb-navy lg:justify-end">
                <button type="button" id="sidebar-buka" aria-label="Buka menu"
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-pwb-navy text-white lg:hidden">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-pwb-navy text-xs text-white">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <span class="hidden truncate sm:inline">{{ auth()->user()?->username ?? auth()->user()?->email ?? 'Admin PWB' }}</span>

                    {{-- Tombol keluar --}}
                    <form method="POST" action="{{ route('admin.logout') }}" onsubmit="return confirm('Keluar dari dashboard admin?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 sm:ml-2">
                            <i class="fa-solid fa-right-from-bracket"></i> Keluar
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h1 class="text-2xl font-extrabold text-pwb-navy sm:text-4xl">@yield('title')</h1>
                    <p class="mt-1 text-sm text-slate-800 sm:mt-2 sm:text-base">@yield('subtitle')</p>
                </div>
                @yield('banner_action')
            </div>
        </header>

        <section class="p-4 sm:p-6">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
                    <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ session('error') }}
                </div>
            @endif

            @yield('content')
        </section>
    </main>
</div>
<script>
    // Tabel → kartu di HP: label setiap kolom diambil dari judul tabel (thead)
    (function () {
        document.querySelectorAll('table').forEach(function (t) {
            if (!t.tHead || !t.tHead.rows.length || t.querySelector('input:not([type=hidden]), select, textarea')) return;
            var label = Array.prototype.map.call(t.tHead.rows[0].cells, function (th) { return th.textContent.trim(); });
            t.classList.add('tabel-hp');
            Array.prototype.forEach.call(t.tBodies, function (tb) {
                Array.prototype.forEach.call(tb.rows, function (tr) {
                    var i = 0, judulDipasang = false;
                    Array.prototype.forEach.call(tr.cells, function (td) {
                        var l = label[i] || '';
                        // Isi sel yang lebih dari satu bagian (mis. email + telepon) dibungkus jadi satu kolom
                        var bagian = Array.prototype.filter.call(td.childNodes, function (n) {
                            return n.nodeType === 1 || (n.nodeType === 3 && n.textContent.trim());
                        });
                        if (bagian.length > 1) {
                            var isi = document.createElement('div');
                            isi.className = 'hp-isi';
                            bagian.forEach(function (n) { isi.appendChild(n); });
                            td.appendChild(isi);
                        }
                        if (!td.hasAttribute('colspan')) {
                            td.setAttribute('data-label', l);
                            if (/^(no\.?|#)$/i.test(l)) td.classList.add('hp-sembunyi');      // nomor urut tidak perlu di HP
                            else if (!judulDipasang && !td.querySelector('img')) { td.classList.add('hp-judul'); judulDipasang = true; }
                        }
                        i += td.colSpan || 1;
                    });
                });
            });
        });
    })();

    // Menu samping: di HP disembunyikan dan dibuka lewat tombol ☰
    (function () {
        var sidebar = document.getElementById('sidebar');
        var backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar) return;

        function buka() {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            backdrop.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
        function tutup() {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            backdrop.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        document.getElementById('sidebar-buka').addEventListener('click', buka);
        document.getElementById('sidebar-tutup').addEventListener('click', tutup);
        backdrop.addEventListener('click', tutup);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutup(); });
        window.addEventListener('resize', function () { if (window.innerWidth >= 1024) tutup(); });
    })();
</script>
@stack('scripts')
</body>
</html>
