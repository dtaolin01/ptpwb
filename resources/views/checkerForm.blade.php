@extends('layoutsAdmin.app')

@section('page_title', $checker->exists ? 'Ubah Checker' : 'Tambah Checker')
@section('title', $checker->exists ? 'Ubah Akun Checker' : 'Tambah Akun Checker')
@section('subtitle', 'Nomor telepon dan PIN ini dipakai untuk login aplikasi PWB Checker')

@section('content')
    @php
        $input = 'w-full rounded-lg border border-pwb-line bg-white px-4 py-3 text-sm outline-none focus:border-pwb-blue';
        $label = 'mb-1 block text-sm font-bold text-pwb-navy';
    @endphp

    <form method="POST"
          action="{{ $checker->exists ? route('checker.update', $checker) : route('checker.store') }}"
          class="max-w-xl rounded-xl border border-pwb-line bg-white p-6">
        @csrf
        @if ($checker->exists) @method('PUT') @endif

        <div class="space-y-5">
            <div>
                <label for="nama" class="{{ $label }}">Nama Petugas</label>
                <input id="nama" name="nama" value="{{ old('nama', $checker->nama) }}" class="{{ $input }}">
                @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="telepon" class="{{ $label }}">Nomor Telepon (untuk login)</label>
                <input id="telepon" name="telepon" value="{{ old('telepon', $checker->telepon) }}" placeholder="0812xxxxxxxx" class="{{ $input }}">
                @error('telepon') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="pin" class="{{ $label }}">PIN (4–8 angka)</label>
                <input id="pin" name="pin" type="password" inputmode="numeric" autocomplete="new-password"
                       placeholder="{{ $checker->exists ? 'Kosongkan kalau tidak diubah' : 'Contoh: 123456' }}" class="{{ $input }}">
                @error('pin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-3 text-sm font-bold text-pwb-navy">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $checker->aktif ?? true)) class="h-4 w-4">
                Akun aktif (bisa login aplikasi)
            </label>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="rounded-lg bg-pwb-blue px-6 py-3 text-sm font-bold text-white hover:bg-blue-700">Simpan</button>
            <a href="{{ route('checker') }}" class="rounded-lg border border-pwb-line px-6 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Batal</a>
        </div>
    </form>
@endsection
