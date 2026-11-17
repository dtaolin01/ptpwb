<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Cari titik lokasi pelanggan lama yang belum punya koordinat (dari alamatnya).
|   php artisan pelanggan:lokasi
*/
Artisan::command('pelanggan:lokasi {--semua : Cari ulang semua pelanggan}', function () {
    $q = \App\Models\Pelanggan::query();
    if (! $this->option('semua')) {
        $q->whereNull('lat');
    }

    $daftar = $q->get();
    $this->info("Mencari lokasi {$daftar->count()} pelanggan...");

    foreach ($daftar as $pl) {
        $ok = \App\Http\Controllers\PelangganController::isiLokasi($pl);
        $this->line(($ok ? '  ✓ ' : '  ✗ ').$pl->nama.($ok ? " ({$pl->lat}, {$pl->lng})" : ' — tidak ditemukan, atur manual di peta'));
        sleep(1);   // aturan Nominatim: maks. 1 permintaan/detik
    }
})->purpose('Isi titik lokasi pelanggan dari alamat');
