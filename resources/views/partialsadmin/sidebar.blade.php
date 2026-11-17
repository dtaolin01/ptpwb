@php
    // [label, nama route (untuk link), ikon FontAwesome, pola route yang dianggap "aktif"]
    $menus = [
        ['Dashboard',     'dashboard',     'fa-house',        ['dashboard']],
        ['Tracking',      'trackingAdmin', 'fa-location-dot', ['trackingAdmin']],
        ['Truck & Supir', 'truck',         'fa-truck',        ['truck', 'truck.*', 'supir', 'supir.*']],
        ['Pelanggan',     'pelanggan',     'fa-users',        ['pelanggan', 'pelanggan.*']],
        ['Pesan Masuk',   'pesan',         'fa-envelope',     ['pesan', 'pesan.*']],
    ];

    $pesanBaru = \App\Models\ContactMessage::where('dibaca', false)->count();
@endphp

<aside class="relative flex min-h-screen w-64 shrink-0 flex-col overflow-hidden bg-pwb-navy text-white">
    <div class="px-5 pt-8">
        <img src="{{ asset('images/logo-pwb.png') }}" alt="Putra Wijaya Berjaya" class="w-full">
    </div>

    <nav class="mt-12 space-y-2 px-3">
        @foreach ($menus as [$label, $route, $ikon, $aktif])
            <a href="{{ route($route) }}"
               class="flex items-center gap-4 rounded-lg px-4 py-3 text-sm {{ request()->routeIs(...$aktif) ? 'bg-pwb-blue font-bold' : 'font-medium hover:bg-white/10' }}">
                <i class="fa-solid {{ $ikon }} w-5 text-center text-base"></i>
                <span class="flex-1">{{ $label }}</span>
                @if ($route === 'pesan' && $pesanBaru > 0)
                    <span class="rounded-full bg-pwb-red px-2 py-0.5 text-xs font-bold">{{ $pesanBaru }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('admin.logout') }}" class="relative z-10 mt-auto px-7 pb-8">
        @csrf
        <button type="submit" class="flex items-center gap-4 text-sm hover:text-white/80">
            <i class="fa-solid fa-right-from-bracket w-5 text-center text-base"></i> Logout
        </button>
    </form>

    {{-- Hiasan segitiga merah di pojok bawah --}}
    <div class="absolute bottom-0 right-0 h-16 w-32 bg-pwb-red" style="clip-path: polygon(100% 0, 100% 100%, 0 100%)"></div>
</aside>
