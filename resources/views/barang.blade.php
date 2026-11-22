@extends('layoutsAdmin.app')

@section('page_title', 'Data Barang')
@section('title', 'Data Barang')
@section('subtitle', 'Barang tersimpan untuk surat jalan. Di form pengiriman cukup ketik / pilih nama barang, kode & satuan terisi otomatis.')

@section('content')
    @php
        $input = 'w-full rounded-lg border border-pwb-line bg-white px-4 py-3 text-sm outline-none focus:border-pwb-blue';
        $label = 'mb-1 block text-xs font-bold text-pwb-navy';
    @endphp

    {{-- Tambah / ubah --}}
    <form method="POST" action="{{ $ubah ? route('barang.update', $ubah) : route('barang.store') }}" class="rounded-xl border border-pwb-line bg-white p-5">
        @csrf
        @if ($ubah) @method('PUT') @endif
        <h2 class="mb-3 font-bold text-pwb-navy">
            <i class="fa-solid {{ $ubah ? 'fa-pen' : 'fa-plus' }} mr-1"></i> {{ $ubah ? 'Ubah barang' : 'Tambah barang' }}
        </h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[2fr_1fr_1fr_auto] sm:items-end">
            <div>
                <label for="nama" class="{{ $label }}">Nama barang</label>
                <input id="nama" name="nama" value="{{ old('nama', $ubah?->nama) }}" required placeholder="Semen Conch 50kg" class="{{ $input }}">
                @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="kode" class="{{ $label }}">Kode barang</label>
                <input id="kode" name="kode" value="{{ old('kode', $ubah?->kode) }}" placeholder="SMN-50" class="{{ $input }}">
            </div>
            <div>
                <label for="satuan" class="{{ $label }}">Satuan</label>
                <input id="satuan" name="satuan" value="{{ old('satuan', $ubah?->satuan) }}" list="pilihan-satuan" placeholder="sak" class="{{ $input }}">
                <datalist id="pilihan-satuan">
                    @foreach (['sak', 'zak', 'ton', 'kg', 'pcs', 'unit', 'batang', 'lembar', 'dus', 'm³', 'rit', 'liter'] as $s)<option value="{{ $s }}">@endforeach
                </datalist>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-pwb-blue px-5 py-3 text-sm font-bold text-white hover:bg-blue-700">Simpan</button>
                @if ($ubah)<a href="{{ route('barang') }}" class="rounded-lg border border-pwb-line px-4 py-3 text-sm font-bold text-pwb-navy">Batal</a>@endif
            </div>
        </div>
    </form>

    <form method="GET" action="{{ route('barang') }}" class="mt-5 flex overflow-hidden rounded-lg border border-pwb-line bg-white">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / kode barang..." class="flex-1 px-4 py-3 text-sm outline-none placeholder:text-slate-400">
        <button type="submit" class="bg-pwb-blue px-5 text-white"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>

    <div class="mt-5 overflow-x-auto rounded-xl bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100/80 text-xs font-bold text-pwb-navy">
                <tr>
                    <th class="px-4 py-4">No.</th>
                    <th class="px-4 py-4">Nama Barang</th>
                    <th class="px-4 py-4">Kode</th>
                    <th class="px-4 py-4">Satuan</th>
                    <th class="px-4 py-4">Dipakai</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-pwb-line">
                @forelse ($data as $b)
                    <tr>
                        <td class="px-4 py-4">{{ $data->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-4 font-bold">{{ $b->nama }}</td>
                        <td class="px-4 py-4">{{ $b->kode ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $b->satuan ?: '-' }}</td>
                        <td class="px-4 py-4 text-slate-500">{{ $b->dipakai }}×</td>
                        <td class="px-4 py-4">@include('partialsadmin.aksi', ['edit' => route('barang', ['ubah' => $b->id]), 'hapus' => route('barang.destroy', $b), 'nama' => $b->nama])</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada barang. Barang juga tersimpan otomatis saat Anda membuat pengiriman.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partialsadmin.pagination', ['data' => $data])
@endsection
