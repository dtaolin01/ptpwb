<?php

namespace App\Support;

use App\Models\LokasiTruk;
use App\Models\NotifikasiPabrik;
use App\Models\Pengiriman;
use App\Models\Truck;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Mendeteksi truk mendekati / tiba di / keluar dari pabrik dari lokasi HP supir,
 * lalu menyimpan notifikasi untuk aplikasi PWB Checker.
 *
 * Zona (diatur di menu Lokasi Pabrik):
 *   - radius_pabrik  : di dalam pabrik
 *   - jarak_dekat km : mendekati pabrik
 */
class DeteksiPabrik
{
    /** Lokasi lebih lama dari ini dianggap tidak diketahui (truk baru muncul lagi). */
    public const MAKS_JAM_LOKASI = 6;

    /** Truk ditampilkan di aplikasi checker kalau lokasi terakhirnya tidak lebih lama dari ini. */
    public const MAKS_JAM_TAMPIL = 3;

    /**
     * Panggil SEBELUM lokasi baru disimpan, supaya lokasi sebelumnya masih bisa dibandingkan.
     */
    public static function periksa(Truck $truck, float $lat, float $lng, CarbonInterface $waktu, ?Pengiriman $p): ?NotifikasiPabrik
    {
        $atur = Pabrik::data();
        $pabrik = Pabrik::titik();
        if (! $pabrik) {
            return null;
        }

        $sebelumnya = LokasiTruk::where('truck_id', $truck->id)
            ->orderByDesc('waktu')
            ->orderByDesc('id')
            ->first();

        // Lokasi lama yang datang terlambat tidak memicu notifikasi
        if ($sebelumnya && $waktu->lt($sebelumnya->waktu)) {
            return null;
        }

        $jarak = ProgresPengiriman::jarak([$lat, $lng], $pabrik);
        $jarakSebelumnya = $sebelumnya && $sebelumnya->waktu->gte($waktu->copy()->subHours(self::MAKS_JAM_LOKASI))
            ? ProgresPengiriman::jarak([(float) $sebelumnya->lat, (float) $sebelumnya->lng], $pabrik)
            : INF;

        $radius = $atur['radius_pabrik'];
        $dekat = $atur['jarak_dekat'] * 1000;
        $batasKeluar = max($radius * 1.5, $radius + 200);   // sama dengan deteksi selesai muat

        $nama = $truck->supir?->nama ?? 'Supir';
        $label = $nama.' – '.$truck->plat;

        [$jenis, $judul, $pesan] = match (true) {
            $jarak <= $radius && $jarakSebelumnya > $radius
                => ['tiba', 'Truk tiba di pabrik', $label.' sudah masuk area '.$atur['nama'].'.'],
            $jarak <= $dekat && $jarak > $radius && $jarakSebelumnya > $dekat
                => ['mendekati', 'Truk mendekati pabrik', $label.' sudah mendekati pabrik (±'.self::km($jarak).').'],
            $jarak > $batasKeluar && $jarakSebelumnya <= $radius
                => ['keluar', 'Truk keluar dari pabrik', $label.' sudah keluar dari '.$atur['nama'].'.'],
            default => [null, null, null],
        };

        if (! $jenis) {
            return null;
        }
        if ($p && $jenis !== 'keluar') {
            $pesan = mb_substr($pesan.' Muatan: '.SuratJalan::ringkasMuatan($p), 0, 250);
        }

        return NotifikasiPabrik::create([
            'jenis'         => $jenis,
            'truck_id'      => $truck->id,
            'pengiriman_id' => $p?->id,
            'plat'          => $truck->plat,
            'nama_supir'    => $nama,
            'judul'         => $judul,
            'pesan'         => $pesan,
            'jarak_m'       => (int) round($jarak),
            'waktu'         => $waktu,
        ]);
    }

    /**
     * Truk yang lokasi terakhirnya di area pabrik / zona mendekati.
     *
     * @return array{di_pabrik: Collection, mendekati: Collection}
     */
    public static function trukSekitar(): array
    {
        $atur = Pabrik::data();
        $pabrik = Pabrik::titik();
        if (! $pabrik) {
            return ['di_pabrik' => collect(), 'mendekati' => collect()];
        }

        $terakhir = LokasiTruk::with('truck.supir')
            ->where('waktu', '>=', now()->subHours(self::MAKS_JAM_TAMPIL))
            ->orderByDesc('waktu')
            ->orderByDesc('id')
            ->get()
            ->unique('truck_id')
            ->filter(fn ($l) => $l->truck !== null)
            ->map(function ($l) use ($pabrik) {
                $l->jarak_m = ProgresPengiriman::jarak([(float) $l->lat, (float) $l->lng], $pabrik);

                return $l;
            })
            ->sortBy('jarak_m')
            ->values();

        return [
            'di_pabrik' => $terakhir->filter(fn ($l) => $l->jarak_m <= $atur['radius_pabrik'])->values(),
            'mendekati' => $terakhir->filter(fn ($l) => $l->jarak_m > $atur['radius_pabrik'] && $l->jarak_m <= $atur['jarak_dekat'] * 1000)->values(),
        ];
    }

    /** Apakah titik ini dalam zona mendekati pabrik (untuk interval kirim lokasi 15 menit). */
    public static function dekatPabrik(float $lat, float $lng): bool
    {
        $pabrik = Pabrik::titik();

        return $pabrik && ProgresPengiriman::jarak([$lat, $lng], $pabrik) <= Pabrik::data()['jarak_dekat'] * 1000;
    }

    public static function km(float $meter): string
    {
        return $meter < 1000
            ? round($meter / 10) * 10 .' m'
            : number_format($meter / 1000, 1, ',', '.').' km';
    }
}
