@extends('layoutsAdmin.app')

@section('page_title', 'Checker')
@section('title', 'Akun Checker')
@section('subtitle', 'Petugas pabrik yang memakai aplikasi PWB Checker. Login aplikasi: nomor telepon + PIN.')

@section('banner_action')
    <a href="{{ route('checker.create') }}" class="inline-flex items-center gap-3 rounded-lg bg-pwb-navy px-5 py-3 text-sm font-bold text-white hover:bg-slate-900">
        <i class="fa-solid fa-plus"></i> Tambah Checker
    </a>
@endsection

@section('content')
    <form method="GET" action="{{ route('checker') }}" class="flex overflow-hidden rounded-lg border border-pwb-line bg-white">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama checker..."
               class="flex-1 px-4 py-3 text-sm outline-none placeholder:text-slate-400">
        <button type="submit" class="bg-pwb-blue px-5 text-white"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>

    <div class="mt-5 overflow-x-auto rounded-xl bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100/80 text-xs font-bold text-pwb-navy">
                <tr>
                    <th class="px-4 py-4">No.</th>
                    <th class="px-4 py-4">Nama</th>
                    <th class="px-4 py-4">Telepon (login)</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-pwb-line">
                @forelse ($data as $c)
                    <tr>
                        <td class="px-4 py-5">{{ $data->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-5 font-bold">{{ $c->nama }}</td>
                        <td class="px-4 py-5">{{ $c->telepon }}</td>
                        <td class="px-4 py-5">
                            <x-badge :warna="$c->aktif ? 'green' : 'red'">{{ $c->aktif ? 'Aktif' : 'Nonaktif' }}</x-badge>
                        </td>
                        <td class="px-4 py-5">@include('partialsadmin.aksi', ['edit' => route('checker.edit', $c), 'hapus' => route('checker.destroy', $c), 'nama' => $c->nama])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada akun checker.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partialsadmin.pagination', ['data' => $data])
@endsection
