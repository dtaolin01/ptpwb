@extends('layoutsAdmin.app')

@section('page_title', 'Pesan Masuk')
@section('title', 'Pesan Masuk')
@section('subtitle', 'Pesan dari form "Kirim Pesan" di halaman Contact'.($belumDibaca ? " · {$belumDibaca} belum dibaca" : ''))

@section('content')
    {{-- Filter --}}
    <form method="GET" action="{{ route('pesan') }}" class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama, email, atau subjek..."
               class="w-full rounded-lg border border-pwb-line bg-white py-3 pl-11 pr-4 text-sm outline-none placeholder:text-slate-400">
    </form>

    {{-- Tabel --}}
    <div class="mt-5 overflow-x-auto rounded-xl bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100/80 text-xs font-bold text-pwb-navy">
                <tr>
                    <th class="px-4 py-4">No.</th>
                    <th class="px-4 py-4">Tanggal</th>
                    <th class="px-4 py-4">Nama</th>
                    <th class="px-4 py-4">Email / Telepon</th>
                    <th class="px-4 py-4">Subjek</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-pwb-line">
                @forelse ($data as $p)
                    <tr class="{{ $p->dibaca ? '' : 'bg-pwb-sky/40' }}">
                        <td class="px-4 py-5">{{ $data->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-5 text-xs">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-5 font-bold">{{ $p->nama }}</td>
                        <td class="px-4 py-5 text-xs">
                            <span class="text-pwb-blue">{{ $p->email }}</span><br>{{ $p->telepon ?: '-' }}
                        </td>
                        <td class="px-4 py-5">
                            <span class="font-semibold">{{ $p->subjek }}</span>
                            <p class="mt-1 max-w-xs truncate text-xs text-slate-500">{{ $p->pesan }}</p>
                        </td>
                        <td class="px-4 py-5">
                            @if ($p->dibaca)
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Dibaca</span>
                            @else
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-pwb-red">Baru</span>
                            @endif
                        </td>
                        <td class="px-4 py-5">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('pesan.show', $p) }}" title="Lihat" class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-700 text-xs text-white hover:bg-blue-800">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <form method="POST" action="{{ route('pesan.destroy', $p) }}"
                                      onsubmit="return confirm({{ \Illuminate\Support\Js::from('Hapus pesan dari '.$p->nama.'?') }})">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus" class="flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-xs text-white hover:bg-red-700">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada pesan masuk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partialsadmin.pagination', ['data' => $data])
@endsection
