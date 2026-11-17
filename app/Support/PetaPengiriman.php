<?php

namespace App\Support;

use App\Models\Pengiriman;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Menyusun data peta (pin asal/tujuan, posisi truk, rute jalan) untuk
 * public/js/pwb-tracking-map.js. Dipakai halaman /tracking dan admin Tracking.
 */
class PetaPengiriman
{
    public static function untuk(Pengiriman $p): array
    {
        $pins = [];

        if ($p->asal_lat !== null && $p->asal_lng !== null) {
            $pins[] = [
                'type' => 'origin',
                'name' => 'Asal: '.($p->asal ?: 'Titik muat'),
                'note' => $p->tanggal_kirim ? 'Berangkat '.$p->tanggal_kirim->locale('id')->translatedFormat('d M Y') : null,
                'lat'  => (float) $p->asal_lat,
                'lng'  => (float) $p->asal_lng,
            ];
        }

        if ($p->tujuan_lat !== null && $p->tujuan_lng !== null) {
            $pins[] = [
                'type' => 'destination',
                'name' => 'Tujuan: '.$p->tujuan,
                'note' => $p->estimasi_tiba ? 'Estimasi tiba '.$p->estimasi_tiba->locale('id')->translatedFormat('d M Y') : null,
                'lat'  => (float) $p->tujuan_lat,
                'lng'  => (float) $p->tujuan_lng,
            ];
        }

        $trucks = [];

        if ($p->posisi_lat !== null && $p->posisi_lng !== null) {
            $trucks[] = [
                'name'       => $p->truck ? 'Truk '.$p->truck->jenis.' '.$p->truck->plat : 'Posisi Truk',
                'lat'        => (float) $p->posisi_lat,
                'lng'        => (float) $p->posisi_lng,
                'icon'       => null,
                'updated_at' => $p->posisi_update?->locale('id')->translatedFormat('d M Y, H.i').' WITA',
            ];
        }

        return [
            'pins'   => $pins,
            'trucks' => $trucks,
            'route'  => self::rute($pins),
        ];
    }

    /**
     * Rute yang mengikuti jalan (OSRM), disimpan di cache 30 hari.
     * Null kalau gagal; peta lalu memakai garis lurus putus-putus.
     */
    private static function rute(array $pins): ?array
    {
        if (count($pins) < 2) {
            return null;
        }

        // OSRM memakai urutan longitude,latitude
        $coords = collect($pins)
            ->map(fn ($pin) => number_format($pin['lng'], 6, '.', '').','.number_format($pin['lat'], 6, '.', ''))
            ->implode(';');

        $key = 'tracking_route_'.md5($coords);

        if (Cache::has($key)) {
            return Cache::get($key);
        }

        try {
            $response = Http::timeout(8)->get('https://router.project-osrm.org/route/v1/driving/'.$coords, [
                'overview'   => 'full',
                'geometries' => 'geojson',
            ]);

            if ($response->successful() && $response->json('code') === 'Ok') {
                // GeoJSON memakai [lng, lat], Leaflet memakai [lat, lng]
                $route = collect($response->json('routes.0.geometry.coordinates', []))
                    ->map(fn ($c) => [round($c[1], 5), round($c[0], 5)])
                    ->all();

                Cache::put($key, $route, now()->addDays(30));

                return $route;
            }
        } catch (\Throwable $e) {
            // Offline / server rute sibuk: garis lurus sebagai cadangan (tidak di-cache, dicoba lagi nanti)
        }

        return null;
    }
}
