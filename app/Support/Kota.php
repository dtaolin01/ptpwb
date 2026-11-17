<?php

namespace App\Support;

/**
 * Koordinat kota yang sering dipakai, untuk mengisi titik peta otomatis
 * kalau admin hanya menulis nama kota tanpa memilih titik di peta.
 */
class Kota
{
    public const KOORDINAT = [
        'samarinda'   => [-0.5022, 117.1536],
        'balikpapan'  => [-1.2379, 116.8529],
        'bontang'     => [0.1333, 117.5000],
        'tenggarong'  => [-0.4167, 116.9833],
        'sangatta'    => [0.4950, 117.5500],
        'penajam'     => [-1.2960, 116.7110],
        'banjarmasin' => [-3.3186, 114.5944],
        'jakarta'     => [-6.2088, 106.8456],
        'tangerang'   => [-6.1781, 106.6300],
        'bekasi'      => [-6.2383, 106.9756],
        'bandung'     => [-6.9175, 107.6191],
        'cirebon'     => [-6.7320, 108.5523],
        'semarang'    => [-6.9667, 110.4167],
        'yogyakarta'  => [-7.7956, 110.3695],
        'surakarta'   => [-7.5755, 110.8243],
        'solo'        => [-7.5755, 110.8243],
        'surabaya'    => [-7.2575, 112.7521],
        'malang'      => [-7.9666, 112.6326],
        'makassar'    => [-5.1477, 119.4327],
    ];

    /** @return array{0: float, 1: float}|null */
    public static function cari(?string $teks): ?array
    {
        $teks = mb_strtolower((string) $teks);

        foreach (self::KOORDINAT as $nama => $titik) {
            if ($teks !== '' && str_contains($teks, $nama)) {
                return $titik;
            }
        }

        return null;
    }
}
