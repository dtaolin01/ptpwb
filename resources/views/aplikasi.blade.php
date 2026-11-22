@extends('layoutsAdmin.app')

@section('page_title', 'Update Aplikasi')
@section('title', 'Update Aplikasi')
@section('subtitle', 'Unggah APK versi baru. Aplikasi Supir & Checker di HP memperbarui dirinya sendiri saat dibuka.')

@section('content')
    @php
        $input = 'w-full rounded-lg border border-pwb-line bg-white px-4 py-3 text-sm outline-none focus:border-pwb-blue';
        $label = 'mb-1 block text-sm font-bold text-pwb-navy';
        $err   = fn ($f) => $errors->first($f) ? '<p class="mt-1 text-xs text-red-600">'.e($errors->first($f)).'</p>' : '';
    @endphp

    {{-- Versi terbaru + link pemasangan pertama --}}
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        @foreach ($daftar as $kode => $nama)
            @php $v = $terbaru[$kode]; $link = route('unduh.aplikasi', $kode); @endphp
            <div class="rounded-xl border border-pwb-line bg-white p-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-pwb-navy text-white">
                        <i class="fa-solid {{ $kode === 'supir' ? 'fa-truck' : 'fa-user-check' }}"></i>
                    </span>
                    <div>
                        <h2 class="font-bold text-pwb-navy">{{ $nama }}</h2>
                        <p class="text-xs text-slate-500">
                            @if ($v)
                                Versi terbaru <b>{{ $v->version_name }}</b> (code {{ $v->version_code }}) · {{ $v->ukuran_label }} · {{ $v->created_at->locale('id')->translatedFormat('d M Y') }}
                            @else
                                Belum ada APK yang diunggah
                            @endif
                        </p>
                    </div>
                </div>

                @if ($v)
                    <p class="mt-4 text-xs font-bold text-pwb-navy">Link pemasangan pertama (kirim ke HP {{ $kode === 'supir' ? 'supir' : 'checker' }}):</p>
                    <div class="mt-1 flex gap-2">
                        <input readonly value="{{ $link }}" class="min-w-0 flex-1 rounded-lg border border-pwb-line bg-slate-50 px-3 py-2 text-xs" onclick="this.select()">
                        <button type="button" class="salin rounded-lg border border-pwb-line px-3 text-xs font-bold text-pwb-blue hover:bg-slate-50" data-link="{{ $link }}">
                            <i class="fa-regular fa-copy mr-1"></i>Salin
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Unggah versi baru --}}
    <form method="POST" action="{{ route('aplikasi.store') }}" enctype="multipart/form-data"
          class="mt-5 rounded-xl border border-pwb-line bg-white p-6">
        @csrf
        <h2 class="mb-1 font-bold text-pwb-navy"><i class="fa-solid fa-cloud-arrow-up mr-2"></i>Unggah Versi Baru</h2>
        <p class="mb-5 text-xs text-slate-500">
            Cukup pilih aplikasi dan file APK; versi dibaca otomatis dari APK.
            Sebelum build, naikkan <code>versionCode</code> di <code>build.gradle</code> dan pakai <b>keystore yang sama</b>.
        </p>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="aplikasi" class="{{ $label }}">Aplikasi</label>
                <select id="aplikasi" name="aplikasi" class="{{ $input }}">
                    @foreach ($daftar as $kode => $nama)
                        <option value="{{ $kode }}" @selected(old('aplikasi') === $kode)>
                            {{ $nama }}{{ $terbaru[$kode] ? ' (sekarang code '.$terbaru[$kode]->version_code.')' : '' }}
                        </option>
                    @endforeach
                </select>
                {!! $err('aplikasi') !!}
            </div>
            <div>
                <label for="file" class="{{ $label }}">File APK</label>
                <input id="file" type="file" name="file" accept=".apk,application/vnd.android.package-archive"
                       class="{{ $input }} file:mr-3 file:rounded-md file:border-0 file:bg-pwb-sky file:px-3 file:py-1 file:text-xs file:font-bold file:text-pwb-navy">
                {!! $err('file') !!}
            </div>
            <div>
                <label for="version_code" class="{{ $label }}">Version code <span class="font-normal text-slate-400">(otomatis dari APK)</span></label>
                <input id="version_code" type="number" min="1" name="version_code" value="{{ old('version_code') }}" placeholder="Boleh dikosongkan" class="{{ $input }}">
                <p class="mt-1 text-xs text-slate-500">Dibaca langsung dari file APK. Isi hanya kalau diminta.</p>
                {!! $err('version_code') !!}
            </div>
            <div>
                <label for="version_name" class="{{ $label }}">Version name <span class="font-normal text-slate-400">(otomatis dari APK)</span></label>
                <input id="version_name" name="version_name" value="{{ old('version_name') }}" placeholder="Boleh dikosongkan" class="{{ $input }}">
                {!! $err('version_name') !!}
            </div>
            <div class="md:col-span-2">
                <label for="catatan" class="{{ $label }}">Apa yang baru (tampil di HP)</label>
                <textarea id="catatan" name="catatan" rows="3" placeholder="Contoh: Peta bisa digeser & di-zoom. Perbaikan notifikasi." class="{{ $input }}">{{ old('catatan') }}</textarea>
                {!! $err('catatan') !!}
            </div>
        </div>

        <label class="mt-5 flex items-start gap-3 text-sm text-pwb-navy">
            <input type="hidden" name="wajib" value="0">
            <input type="checkbox" name="wajib" value="1" @checked(old('wajib')) class="mt-1 h-4 w-4">
            <span><b>Wajib update</b><br><span class="text-xs text-slate-500">Aplikasi tidak bisa dipakai sebelum diperbarui (tombol "Nanti" disembunyikan). Pakai untuk perubahan penting.</span></span>
        </label>

        <button type="submit" class="mt-6 rounded-lg bg-pwb-blue px-6 py-3 text-sm font-bold text-white hover:bg-blue-700">
            <i class="fa-solid fa-upload mr-2"></i>Unggah
        </button>
    </form>

    {{-- Riwayat versi --}}
    <div class="mt-5 rounded-xl border border-pwb-line bg-white p-5">
        <h2 class="font-bold text-pwb-navy">Riwayat Versi</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100/80 text-xs font-bold text-pwb-navy">
                    <tr>
                        <th class="px-4 py-3">Aplikasi</th>
                        <th class="px-4 py-3">Versi</th>
                        <th class="px-4 py-3">Apa yang baru</th>
                        <th class="px-4 py-3">Ukuran</th>
                        <th class="px-4 py-3">Diunggah</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-pwb-line">
                    @forelse ($versi->flatten() as $v)
                        <tr>
                            <td class="px-4 py-4 font-bold">{{ $daftar[$v->aplikasi] ?? $v->aplikasi }}</td>
                            <td class="px-4 py-4">
                                {{ $v->version_name }} <span class="text-xs text-slate-500">(code {{ $v->version_code }})</span>
                                @if ($v->wajib) <x-badge warna="red">Wajib</x-badge> @endif
                                @if ($terbaru[$v->aplikasi]?->id === $v->id) <x-badge warna="green">Terbaru</x-badge> @endif
                            </td>
                            <td class="max-w-xs px-4 py-4 text-xs">{{ $v->catatan ?: '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-xs">{{ $v->ukuran_label }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-xs">{{ $v->created_at->locale('id')->translatedFormat('d M Y, H.i') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('unduh.aplikasi', ['aplikasi' => $v->aplikasi, 'id' => $v->id]) }}" class="text-pwb-blue hover:underline" title="Unduh">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                    <form method="POST" action="{{ route('aplikasi.destroy', $v) }}"
                                          onsubmit="return confirm('Hapus {{ $daftar[$v->aplikasi] ?? '' }} versi {{ $v->version_name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada APK yang diunggah.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.salin').forEach(function (b) {
            b.addEventListener('click', function () {
                navigator.clipboard && navigator.clipboard.writeText(b.dataset.link);
                b.innerHTML = '<i class="fa-solid fa-check mr-1"></i>Tersalin';
                setTimeout(function () { b.innerHTML = '<i class="fa-regular fa-copy mr-1"></i>Salin'; }, 1500);
            });
        });
    </script>
@endpush
