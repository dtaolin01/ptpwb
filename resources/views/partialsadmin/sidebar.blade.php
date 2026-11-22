@php
    // [label, nama route (untuk link), ikon FontAwesome, pola route yang dianggap "aktif"]
    $menus = [
        ['Dashboard',     'dashboard',     'fa-house',        ['dashboard']],
        ['Tracking',      'trackingAdmin', 'fa-location-dot', ['trackingAdmin', 'trackingAdmin.*']],
        ['Peta Armada',   'petaArmada',    'fa-map-location-dot', ['petaArmada', 'petaArmada.*']],
        ['Truck & Supir', 'truck',         'fa-truck',        ['truck', 'truck.*', 'supir', 'supir.*']],
        ['Pelanggan',     'pelanggan',     'fa-users',        ['pelanggan', 'pelanggan.*']],
        ['Lokasi Pabrik', 'pabrik',        'fa-industry',     ['pabrik', 'pabrik.*']],
        ['Data Barang',   'barang',        'fa-boxes-stacked', ['barang', 'barang.*']],
        ['Checker',       'checker',       'fa-user-check',   ['checker', 'checker.*']],
        ['Update Aplikasi','aplikasi',     'fa-mobile-screen-button', ['aplikasi', 'aplikasi.*']],
        ['WhatsApp Otomatis', 'whatsapp',  'fa-brands fa-whatsapp', ['whatsapp', 'whatsapp.*']],
        ['Pesan Masuk',   'pesan',         'fa-envelope',     ['pesan', 'pesan.*']],
    ];

    $pesanBaru = \App\Models\ContactMessage::where('dibaca', false)->count();

    // Menu persetujuan akun hanya untuk pemilik website
    $akunMenunggu = 0;
    if (auth()->user()?->isPemilik()) {
        $menus[] = ['Persetujuan Akun', 'akunAdmin', 'fa-user-shield', ['akunAdmin', 'akunAdmin.*']];
        $akunMenunggu = \App\Models\User::where('disetujui', false)->count();
    }
@endphp

{{-- Laptop: menempel di kiri. HP/tablet (< 1024px): tersembunyi, dibuka lewat tombol ☰ di header --}}
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-[1100] flex h-screen w-64 shrink-0 -translate-x-full flex-col overflow-hidden bg-pwb-navy text-white shadow-2xl transition-transform duration-200 lg:sticky lg:top-0 lg:z-auto lg:translate-x-0 lg:shadow-none">
    <div class="relative px-5 pt-8">
        <img src="{{ asset('images/logo-pwb.png') }}" alt="Putra Wijaya Berjaya" class="w-full">
        <button type="button" id="sidebar-tutup" aria-label="Tutup menu"
                class="absolute right-2 top-2 flex h-9 w-9 items-center justify-center rounded-lg text-white/80 hover:bg-white/10 lg:hidden">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <nav class="mt-10 min-h-0 flex-1 space-y-1.5 overflow-y-auto px-3 pb-20 lg:mt-12">
        @foreach ($menus as [$label, $route, $ikon, $aktif])
            <a href="{{ route($route) }}"
               class="flex items-center gap-4 rounded-lg px-4 py-3 text-sm {{ request()->routeIs(...$aktif) ? 'bg-pwb-blue font-bold' : 'font-medium hover:bg-white/10' }}">
                <i class="{{ str_starts_with($ikon, 'fa-brands') ? $ikon : 'fa-solid '.$ikon }} w-5 text-center text-base"></i>
                <span class="flex-1">{{ $label }}</span>
                @if ($route === 'pesan' && $pesanBaru > 0)
                    <span class="rounded-full bg-pwb-red px-2 py-0.5 text-xs font-bold">{{ $pesanBaru }}</span>
                @endif
                @if ($route === 'akunAdmin' && $akunMenunggu > 0)
                    <span class="rounded-full bg-pwb-red px-2 py-0.5 text-xs font-bold">{{ $akunMenunggu }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    {{-- Hiasan segitiga merah di pojok bawah --}}
    <div class="absolute bottom-0 right-0 h-16 w-32 bg-pwb-red" style="clip-path: polygon(100% 0, 100% 100%, 0 100%)"></div>
</aside>
