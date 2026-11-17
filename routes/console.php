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

/*
| Kosongkan data operasional: Supir, Truck, Pelanggan, Tracking (beserta langkah,
| riwayat lokasi, dan login aplikasi supir). Akun admin, isi halaman website
| (layanan, kontak, pesan masuk), dan Lokasi Pabrik TIDAK dihapus.
|   php artisan data:kosongkan
*/
Artisan::command('data:kosongkan {--force : Tanpa konfirmasi}', function () {
    $tabel = [
        'tracking_events' => 'Langkah perjalanan',
        'lokasi_truk'     => 'Riwayat lokasi truk',
        'supir_tokens'    => 'Login aplikasi supir',
        'pengiriman'      => 'Tracking / pengiriman',
        'trucks'          => 'Truck',
        'supir'           => 'Supir',
        'pelanggan'       => 'Pelanggan',
    ];

    $this->warn('Data berikut akan DIHAPUS PERMANEN:');
    foreach ($tabel as $t => $nama) {
        $jumlah = \Illuminate\Support\Facades\Schema::hasTable($t) ? \Illuminate\Support\Facades\DB::table($t)->count() : 0;
        $this->line(sprintf('  - %-24s %d baris', $nama, $jumlah));
    }
    $this->line('Tidak dihapus: akun admin, layanan, kontak, pesan masuk, Lokasi Pabrik.');

    if (! $this->option('force') && ! $this->confirm('Yakin hapus semua data di atas?', false)) {
        $this->info('Dibatalkan.');

        return;
    }

    // TRUNCATE: data kosong + nomor ID mulai dari 1 lagi
    \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
    foreach (array_keys($tabel) as $t) {
        if (\Illuminate\Support\Facades\Schema::hasTable($t)) {
            \Illuminate\Support\Facades\DB::table($t)->truncate();
        }
    }
    \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

    $this->info('Selesai. Supir, Truck, Pelanggan, dan Tracking sekarang kosong.');
})->purpose('Hapus semua data Supir, Truck, Pelanggan, dan Tracking');
