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
    @stack('styles')
</head>
<body class="bg-pwb-bg font-sans text-slate-800">
<div class="flex min-h-screen">

    @include('partialsadmin.sidebar')

    <main class="min-w-0 flex-1">
        {{-- Banner atas: avatar admin + judul halaman --}}
        <header class="bg-pwb-sky px-8 pb-6 pt-4">
            <div class="flex items-center justify-end gap-3 text-sm font-semibold text-pwb-navy">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-pwb-navy text-xs text-white">
                    <i class="fa-solid fa-user"></i>
                </span>
                {{ auth()->user()?->username ?? auth()->user()?->email ?? 'Admin PWB' }}

                {{-- Tombol keluar --}}
                <form method="POST" action="{{ route('admin.logout') }}" onsubmit="return confirm('Keluar dari dashboard admin?')">
                    @csrf
                    <button type="submit" class="ml-2 inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">
                        <i class="fa-solid fa-right-from-bracket"></i> Keluar
                    </button>
                </form>
            </div>

            <div class="mt-4 flex items-end justify-between">
                <div>
                    <h1 class="text-4xl font-extrabold text-pwb-navy">@yield('title')</h1>
                    <p class="mt-2 text-slate-800">@yield('subtitle')</p>
                </div>
                @yield('banner_action')
            </div>
        </header>

        <section class="p-6">
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
@stack('scripts')
</body>
</html>
