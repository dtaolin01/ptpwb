<?php

namespace App\Support;

use App\Models\Pengaturan;

/**
 * Pengaturan pelacakan aplikasi PWB Supir, diatur admin di halaman "Lokasi Pabrik".
 * Dikirim ke aplikasi di setiap jawaban API supir, jadi angka ini bisa diubah
 * tanpa memperbarui aplikasi di HP supir.
 */
class Pelacakan
{
    /** kunci => [bawaan, min, maks, label] */
    public const NILAI = [
        'interval_jalan_detik' => [60, 15, 600, 'Ambil GPS saat truk berjalan, tiap (detik)'],
        'jarak_kirim_m'        => [50, 10, 1000, 'Kirim lokasi tiap truk pindah (meter)'],
        'menit_detak_jalan'    => [5, 1, 60, 'Saat berjalan pelan / macet, tetap kirim tiap (menit)'],
        'menit_sampai_diam'    => [10, 3, 60, 'Dianggap diam setelah tidak berpindah (menit)'],
        'menit_detak_diam'     => [15, 5, 120, 'Saat diam, tetap kirim tiap (menit)'],
        'jarak_bergerak_m'     => [100, 30, 1000, 'Dianggap mulai bergerak setelah pindah (meter)'],
        'akurasi_maks_m'       => [500, 50, 5000, 'Abaikan titik GPS yang melesetnya lebih dari (meter)'],
    ];

    /** @return array<string,int> */
    public static function untukAplikasi(): array
    {
        $hasil = [];
        foreach (self::NILAI as $kunci => [$bawaan, $min, $maks]) {
            $v = (int) Pengaturan::ambil('lacak_'.$kunci, $bawaan);
            $hasil[$kunci] = max($min, min($maks, $v));
        }

        return $hasil;
    }
}
