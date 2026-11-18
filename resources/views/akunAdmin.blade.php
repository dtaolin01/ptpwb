@extends('layoutsAdmin.app')

@section('page_title', 'Persetujuan Akun')
@section('title', 'Persetujuan Akun Admin')
@section('subtitle', 'Akun yang mendaftar lewat halaman Sign Up baru bisa login setelah Anda setujui. Menu ini hanya terlihat oleh Anda.')

@section('content')
    {{-- Menunggu persetujuan --}}
    <div class="rounded-xl bg-white p-5">
        <h2 class="flex items-center gap-3 font-bold text-pwb-navy">
            <i class="fa-solid fa-user-clock"></i> Menunggu Persetujuan
            @if ($menunggu->count())
                <span class="rounded-full bg-pwb-red px-2 py-0.5 text-xs font-bold text-white">{{ $menunggu->count() }}</span>
            @endif
        </h2>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100/80 text-xs font-bold text-pwb-navy">
                    <tr>
                        <th class="px-4 py-3">Username / Email</th>
                        <th class="px-4 py-3">Mendaftar</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-pwb-line">
                    @forelse ($menunggu as $u)
                        <tr>
                            <td class="px-4 py-4 font-bold">{{ $u->label }}</td>
                            <td class="px-4 py-4 text-xs">{{ $u->created_at?->locale('id')->translatedFormat('d M Y, H.i') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('akunAdmin.setujui', $u) }}">
                                        @csrf
                                        <button class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-3 py-2 text-xs font-bold text-white hover:bg-green-700">
                                            <i class="fa-solid fa-check"></i> Setujui
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('akunAdmin.destroy', $u) }}" onsubmit="return confirm('Tolak dan hapus pendaftaran {{ $u->label }}?')">
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center gap-2 rounded-lg bg-pwb-red px-3 py-2 text-xs font-bold text-white hover:opacity-90">
                                            <i class="fa-solid fa-xmark"></i> Tolak
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">Tidak ada pendaftaran yang menunggu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Akun aktif --}}
    <div class="mt-5 rounded-xl bg-white p-5">
        <h2 class="flex items-center gap-3 font-bold text-pwb-navy"><i class="fa-solid fa-user-check"></i> Akun Aktif</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100/80 text-xs font-bold text-pwb-navy">
                    <tr>
                        <th class="px-4 py-3">Username / Email</th>
                        <th class="px-4 py-3">Disetujui</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-pwb-line">
                    @foreach ($disetujui as $u)
                        <tr>
                            <td class="px-4 py-4 font-bold">
                                {{ $u->label }}
                                @if ($u->isPemilik()) <x-badge warna="blue">Pemilik</x-badge> @endif
                            </td>
                            <td class="px-4 py-4 text-xs">{{ $u->disetujui_at?->locale('id')->translatedFormat('d M Y, H.i') ?? '-' }}</td>
                            <td class="px-4 py-4">
                                @if ($u->isPemilik() || $u->is(auth()->user()))
                                    <span class="text-xs text-slate-400">—</span>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('akunAdmin.cabut', $u) }}" onsubmit="return confirm('Cabut akses {{ $u->label }}? Akun ini tidak bisa login lagi sampai disetujui ulang.')">
                                            @csrf
                                            <button class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-3 py-2 text-xs font-bold text-white hover:bg-amber-600">
                                                <i class="fa-solid fa-ban"></i> Cabut Akses
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('akunAdmin.destroy', $u) }}" onsubmit="return confirm('Hapus akun {{ $u->label }}?')">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-2 rounded-lg bg-pwb-red px-3 py-2 text-xs font-bold text-white hover:opacity-90">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
