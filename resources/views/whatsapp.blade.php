@extends('layoutsAdmin.app')

@section('page_title', 'WhatsApp Otomatis')
@section('title', 'WhatsApp Otomatis')
@section('subtitle', 'Surat jalan dikirim otomatis ke WhatsApp pelanggan setiap pengiriman baru dibuat.')

@section('content')
    @php
        $input = 'w-full rounded-lg border border-pwb-line bg-white px-4 py-3 text-sm outline-none focus:border-pwb-blue';
        $label = 'mb-1 block text-sm font-bold text-pwb-navy';
    @endphp

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            {{-- Pengaturan --}}
            <form method="POST" action="{{ route('whatsapp.update') }}" class="rounded-xl border border-pwb-line bg-white p-6">
                @csrf @method('PUT')
                <h2 class="mb-1 font-bold text-pwb-navy"><i class="fa-brands fa-whatsapp mr-2 text-green-600"></i>Pengaturan Fonnte</h2>
                <p class="mb-5 text-xs text-slate-500">
                    Status:
                    @if ($aktif && $adaToken)
                        <span class="rounded-full bg-green-100 px-2 py-0.5 font-bold text-green-700">Aktif — surat jalan terkirim otomatis</span>
                    @elseif ($adaToken)
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 font-bold text-amber-700">Token ada, tetapi pengiriman otomatis dimatikan</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 font-bold text-slate-600">Belum diatur</span>
                    @endif
                </p>

                <label for="token" class="{{ $label }}">Token perangkat Fonnte</label>
                <input id="token" name="token" type="password" autocomplete="off" class="{{ $input }}"
                       placeholder="{{ $adaToken ? 'Tersimpan: '.$tokenSamar.' (kosongkan bila tidak diganti)' : 'Tempel token dari dashboard Fonnte' }}">
                @error('token') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                <label class="mt-5 flex cursor-pointer items-center gap-3">
                    <input type="hidden" name="aktif" value="0">
                    <input type="checkbox" name="aktif" value="1" @checked($aktif) class="h-5 w-5 rounded border-slate-300 text-pwb-blue">
                    <span class="text-sm font-bold text-pwb-navy">Kirim surat jalan otomatis saat pengiriman dibuat</span>
                </label>

                <button type="submit" class="mt-5 rounded-lg bg-pwb-blue px-6 py-3 text-sm font-bold text-white hover:bg-blue-700">Simpan</button>
            </form>

            {{-- Uji coba --}}
            <form method="POST" action="{{ route('whatsapp.tes') }}" class="rounded-xl border border-pwb-line bg-white p-6">
                @csrf
                <h2 class="mb-1 font-bold text-pwb-navy"><i class="fa-solid fa-paper-plane mr-2"></i>Kirim Pesan Uji Coba</h2>
                <p class="mb-4 text-xs text-slate-500">Pastikan token benar & perangkat Fonnte tersambung dengan mengirim pesan ke nomor Anda sendiri.</p>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input name="nomor" value="{{ old('nomor') }}" inputmode="tel" placeholder="0812 3456 7890" class="{{ $input }} sm:max-w-xs">
                    <button type="submit" @disabled(! $adaToken) class="rounded-lg bg-green-600 px-6 py-3 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-50">
                        <i class="fa-brands fa-whatsapp mr-1"></i> Kirim uji coba
                    </button>
                </div>
            </form>
        </div>

        {{-- Petunjuk --}}
        <div class="rounded-xl border border-pwb-line bg-white p-6 text-sm">
            <h2 class="mb-3 font-bold text-pwb-navy"><i class="fa-solid fa-circle-info mr-2"></i>Cara memasang</h2>
            <ol class="list-decimal space-y-2 pl-5 text-slate-700">
                <li>Daftar di <b>fonnte.com</b> dan pilih paket.</li>
                <li>Menu <b>Device</b> → tambah perangkat dengan nomor WhatsApp kantor, lalu <b>Connect</b> dan pindai QR dari aplikasi WhatsApp di HP kantor (Perangkat tertaut).</li>
                <li>Salin <b>Token</b> perangkat, tempel di kolom kiri, centang "Kirim otomatis", lalu <b>Simpan</b>.</li>
                <li>Kirim uji coba ke nomor Anda sendiri.</li>
                <li>Pastikan setiap pelanggan punya <b>No. WhatsApp</b> di menu Pelanggan.</li>
            </ol>
            <div class="mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                HP kantor yang tertaut harus tetap menyala & tersambung internet. Pakai nomor khusus kantor, dan hindari mengirim pesan massal supaya nomor tidak diblokir WhatsApp.
            </div>
            <p class="mt-4 text-xs text-slate-500">
                Status pengiriman tiap surat jalan terlihat di halaman Tracking. Tombol <b>Kirim ulang WA</b> tersedia bila gagal.
            </p>
        </div>
    </div>
@endsection
