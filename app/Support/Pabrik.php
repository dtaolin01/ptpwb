<?php

namespace App\Support;

use App\Models\Pengaturan;

/**
 * Lokasi pabrik (satu pabrik tetap) dan radius deteksi otomatis.
 * Diatur admin di menu "Lokasi Pabrik".
 */
class Pabrik
{
    public const DEFAULT_RADIUS_PABRIK = 300;   // meter
    public const DEFAULT_RADIUS_TUJUAN = 300;   // meter
    public const DEFAULT_JARAK_DEKAT = 5;       // km: lokasi dikirim tiap 15 menit dalam jarak ini

    public static function data(): array
    {
        $lat = Pengaturan::ambil('pabrik_lat');
        $lng = Pengaturan::ambil('pabrik_lng');

        return [
            'nama'          => Pengaturan::ambil('pabrik_nama', 'Pabrik'),
            'alamat'        => Pengaturan::ambil('pabrik_alamat'),
            'lat'           => $lat !== null && $lat !== '' ? (float) $lat : null,
            'lng'           => $lng !== null && $lng !== '' ? (float) $lng : null,
            'radius_pabrik' => (int) Pengaturan::ambil('radius_pabrik', self::DEFAULT_RADIUS_PABRIK),
            'radius_tujuan' => (int) Pengaturan::ambil('radius_tujuan', self::DEFAULT_RADIUS_TUJUAN),
            'jarak_dekat'   => (float) Pengaturan::ambil('jarak_dekat', self::DEFAULT_JARAK_DEKAT),
        ];
    }

    /** [lat, lng] atau null kalau pabrik belum diatur. */
    public static function titik(): ?array
    {
        $d = self::data();

        return $d['lat'] !== null && $d['lng'] !== null ? [$d['lat'], $d['lng']] : null;
    }
}
