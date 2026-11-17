<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cari koordinat dari alamat, dan nama daerah dari koordinat,
 * memakai OpenStreetMap Nominatim (gratis, sama dengan peta website).
 * Aturan Nominatim: maks. 1 permintaan/detik + User-Agent jelas → hasil di-cache.
 */
class Geocoder
{
    private const URL = 'https://nominatim.openstreetmap.org';

    /**
     * Alamat → ['lat' => .., 'lng' => .., 'kota' => .., 'label' => ..] atau null.
     * Kalau alamat lengkap tidak ketemu, dicoba lagi dengan bagian alamat yang lebih umum.
     */
    public static function cari(?string $alamat): ?array
    {
        $alamat = trim(preg_replace('/\s+/', ' ', (string) $alamat));
        if ($alamat === '') {
            return null;
        }

        foreach (self::variasi($alamat) as $i => $q) {
            if ($i > 0) {
                usleep(1_100_000);   // batas 1 permintaan/detik
            }

            $hasil = Cache::remember('geo_cari_'.md5($q), now()->addDays(30), function () use ($q) {
                try {
                    $res = self::http()->get(self::URL.'/search', [
                        'q'              => $q,
                        'format'         => 'jsonv2',
                        'limit'          => 1,
                        'countrycodes'   => 'id',
                        'addressdetails' => 1,
                    ]);

                    return $res->successful() ? ($res->json()[0] ?? false) : null;
                } catch (\Throwable $e) {
                    return null;   // tidak di-cache: coba lagi lain kali
                }
            });

            if ($hasil) {
                return [
                    'lat'   => (float) $hasil['lat'],
                    'lng'   => (float) $hasil['lon'],
                    'kota'  => self::kota($hasil['address'] ?? []),
                    'label' => $hasil['display_name'] ?? $q,
                ];
            }
        }

        // Cadangan terakhir: koordinat kota yang dikenal
        if ($titik = Kota::cari($alamat)) {
            return ['lat' => $titik[0], 'lng' => $titik[1], 'kota' => null, 'label' => 'Perkiraan dari nama kota'];
        }

        return null;
    }

    /**
     * Koordinat → nama daerah singkat, mis. "Sungai Kunjang, Samarinda".
     */
    public static function namaDaerah(float $lat, float $lng): ?string
    {
        $key = 'geo_balik_'.round($lat, 3).'_'.round($lng, 3);

        return Cache::remember($key, now()->addDays(30), function () use ($lat, $lng) {
            try {
                $res = self::http()->get(self::URL.'/reverse', [
                    'lat'    => $lat,
                    'lon'    => $lng,
                    'format' => 'jsonv2',
                    'zoom'   => 14,
                ]);

                if (! $res->successful()) {
                    return null;
                }

                $a = $res->json('address', []);
                $bagian = array_filter([
                    $a['village'] ?? $a['suburb'] ?? $a['city_district'] ?? $a['town'] ?? null,
                    self::kota($a),
                ]);

                return $bagian ? implode(', ', array_unique($bagian)) : null;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    private static function kota(array $a): ?string
    {
        $kota = $a['city'] ?? $a['county'] ?? $a['regency'] ?? $a['town'] ?? $a['municipality'] ?? $a['state'] ?? null;

        return $kota ? trim(preg_replace('/^(Kota|Kabupaten)\s+/i', '', $kota)) : null;
    }

    /** "Jl. A No. 1, Kel. B, Kec. C, Kota D" → [lengkap, "Kel. B, Kec. C, Kota D", "Kec. C, Kota D", "Kota D"] */
    private static function variasi(string $alamat): array
    {
        $bagian = array_values(array_filter(array_map('trim', explode(',', $alamat))));
        $hasil = [$alamat];

        for ($i = 1; $i < count($bagian) && count($hasil) < 4; $i++) {
            $hasil[] = implode(', ', array_slice($bagian, $i));
        }

        return array_values(array_unique($hasil));
    }

    private static function http()
    {
        return Http::timeout(8)
            ->withHeaders([
                'User-Agent'      => 'PT-PWB/1.0 ('.parse_url((string) config('app.url'), PHP_URL_HOST).')',
                'Accept-Language' => 'id',
            ]);
    }
}
