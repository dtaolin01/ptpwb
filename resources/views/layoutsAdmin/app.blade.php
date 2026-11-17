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
        /* Tabel di layar HP: tetap rapi, geser ke samping untuk melihat kolom lain */
        @media (max-width: 767px) {
            .overflow-x-auto > table { min-width: 680px; }
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
