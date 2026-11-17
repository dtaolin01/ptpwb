<?php

namespace App\Support;

use App\Models\LokasiTruk;
use App\Models\Pengiriman;
use App\Models\TrackingEvent;
use Carbon\CarbonInterface;

/**
 * Memajukan status pengiriman secara otomatis dari lokasi HP supir.
 *
 *   menunggu ─▶ menuju_pabrik ─▶ muat ─▶ dalam_perjalanan ─▶ tiba ─▶ (supir konfirmasi) terkirim
 *                    (masuk area pabrik)  (keluar area pabrik)  (masuk area tujuan)
 *
 * Setiap perubahan tahap & setiap posisi rutin (±2 jam) dicatat sebagai langkah perjalanan
 * dengan waktu = timestamp lokasi dari HP.
 */
class ProgresPengiriman
{
    public const INTERVAL_NORMAL = 120;   // menit
    public const INTERVAL_DEKAT = 15;     // menit, saat dekat pabrik / tujuan

    /** Jarak minimal antar langkah "Posisi Truk" supaya tidak terlalu rapat (menit). */
    private const JEDA_POSISI = 90;

    /** Judul langkah standar (harus sama dengan Pengiriman::LANGKAH_STANDAR). */
    public const L_DIBUAT = 'Pesanan Dibuat';
    public const L_MENUJU_PABRIK = 'Menuju Pabrik';
    public const L_MUAT = 'Sedang Muat di Pabrik';
    public const L_PENGIRIMAN = 'Dalam Pengiriman';
    public const L_TIBA = 'Tiba di Tujuan';
    public const L_SELESAI = 'Selesai';
    public const L_POSISI = 'Posisi Truk';

    /**
     * Proses satu lokasi. Mengembalikan interval (menit) sampai lokasi berikutnya dikirim.
     */
    public static function proses(Pengiriman $p, float $lat, float $lng, CarbonInterface $waktu): int
    {
        $pabrik = Pabrik::data();
        $titikPabrik = Pabrik::titik();
        $titikTujuan = self::titikTujuan($p);
        $posisi = [$lat, $lng];

        // Lokasi lama yang datang terlambat (mis. dikirim ulang setelah sinyal kembali):
        // simpan riwayatnya saja, jangan mundurkan status/posisi.
        if ($p->posisi_update && $waktu->lt($p->posisi_update)) {
            $terkini = $p->posisi_lat !== null ? [(float) $p->posisi_lat, (float) $p->posisi_lng] : $posisi;

            return self::interval($p, $terkini, $titikPabrik, $titikTujuan, $pabrik);
        }

        $posisiSebelumnya = $p->posisi_lat !== null ? [(float) $p->posisi_lat, (float) $p->posisi_lng] : null;
        $waktuSebelumnya = $p->posisi_update;

        $ubah = [
            'posisi_lat'    => $lat,
            'posisi_lng'    => $lng,
            'posisi_update' => $waktu,
        ];

        // Titik asal = posisi truk saat pertama kali terdeteksi untuk pesanan ini
        if ($p->asal_lat === null || $p->asal_lng === null) {
            $ubah['asal_lat'] = $lat;
            $ubah['asal_lng'] = $lng;
            if (! $p->asal) {
                $ubah['asal'] = Geocoder::namaDaerah($lat, $lng) ?? 'Posisi awal truk';
            }
        }

        // Tujuan belum punya titik → ambil dari lokasi perusahaan tujuan
        if ($titikTujuan === null && ($t = self::titikPelanggan($p))) {
            $ubah['tujuan_lat'] = $t[0];
            $ubah['tujuan_lng'] = $t[1];
            $titikTujuan = $t;
        }

        $p->fill($ubah);
        $status = $p->status;

        // Pengiriman yang ditandai "Tertunda" oleh admin tidak dimajukan otomatis
        if ($status === 'tertunda') {
            $p->save();

            return self::interval($p, $posisi, $titikPabrik, $titikTujuan, $pabrik);
        }

        $tahapBerubah = false;

        // 1. Pesanan baru: truk mulai bergerak
        if ($status === 'menunggu') {
            if ($titikPabrik) {
                $status = 'menuju_pabrik';
                self::catat($p, self::L_MENUJU_PABRIK, $waktu, $p->asal ?: ($ubah['asal'] ?? null));
            } else {
                // Pabrik belum diatur → langsung tahap pengiriman
                $status = 'dalam_perjalanan';
                self::catat($p, self::L_PENGIRIMAN, $waktu, $p->asal ?: ($ubah['asal'] ?? null));
            }
            $tahapBerubah = true;
        }

        // 2. Menuju pabrik → masuk area pabrik = sedang muat
        if ($status === 'menuju_pabrik' && $titikPabrik) {
            if (self::jarak($posisi, $titikPabrik) <= $pabrik['radius_pabrik']) {
                $status = 'muat';
                $p->waktu_tiba_pabrik = $waktu;
                self::catat($p, self::L_MUAT, $waktu, $pabrik['nama']);
                $tahapBerubah = true;
            } elseif (self::melewatiPabrik($p, $posisi, $titikPabrik, $titikTujuan)) {
                // Truk sudah jauh melewati pabrik ke arah tujuan tapi tidak terdeteksi di pabrik
                // (mis. HP mati / tanpa sinyal saat di pabrik) → catat muat sebagai perkiraan.
                $perkiraan = $waktuSebelumnya ?? $waktu;
                $p->waktu_tiba_pabrik = $perkiraan;
                $p->waktu_keluar_pabrik = $waktu;
                self::catat($p, self::L_MUAT, $perkiraan, $pabrik['nama'].' (perkiraan)');
                $status = 'dalam_perjalanan';
                self::catat($p, self::L_PENGIRIMAN, $waktu, Geocoder::namaDaerah($lat, $lng));
                $tahapBerubah = true;
            }
        }

        // 3. Sedang muat → keluar area pabrik = mulai pengiriman
        //    (batas keluar dibuat lebih longgar supaya GPS yang meleset sedikit tidak memicu)
        if ($status === 'muat' && ! $tahapBerubah && $titikPabrik) {
            $batasKeluar = max($pabrik['radius_pabrik'] * 1.5, $pabrik['radius_pabrik'] + 200);
            if (self::jarak($posisi, $titikPabrik) > $batasKeluar) {
                $status = 'dalam_perjalanan';
                $p->waktu_keluar_pabrik = $waktu;
                self::catat($p, self::L_PENGIRIMAN, $waktu, 'Berangkat dari '.$pabrik['nama']);
                $tahapBerubah = true;
            }
        }

        // 4. Dalam pengiriman → masuk area tujuan = tiba
        if ($status === 'dalam_perjalanan' && $titikTujuan
            && self::jarak($posisi, $titikTujuan) <= $pabrik['radius_tujuan']) {
            $status = 'tiba';
            $p->waktu_tiba_tujuan = $waktu;
            self::catat($p, self::L_TIBA, $waktu, $p->pelanggan?->nama ?? $p->tujuan);
            $tahapBerubah = true;
        }

        // 5. Posisi rutin selama di jalan (±tiap 2 jam) → tambah langkah "Posisi Truk"
        if (! $tahapBerubah && in_array($status, ['menuju_pabrik', 'dalam_perjalanan'], true)) {
            $terakhir = $p->events()->where('selesai', true)->max('waktu');
            if (! $terakhir || $waktu->diffInMinutes($terakhir, true) >= self::JEDA_POSISI) {
                $daerah = Geocoder::namaDaerah($lat, $lng);
                self::tambah($p, self::L_POSISI, $waktu, $daerah ?? sprintf('%.5f, %.5f', $lat, $lng));
            }
        }

        $p->status = $status;
        $p->save();

        self::susunUlang($p);

        return self::interval($p, $posisi, $titikPabrik, $titikTujuan, $pabrik);
    }

    /**
     * Saat supir menekan "Selesaikan Pesanan".
     */
    public static function selesai(Pengiriman $p): void
    {
        $sekarang = now();

        if (! $p->waktu_tiba_tujuan) {
            $p->waktu_tiba_tujuan = $sekarang;
            self::catat($p, self::L_TIBA, $sekarang, $p->pelanggan?->nama ?? $p->tujuan);
        }

        $p->status = 'terkirim';
        $p->save();

        self::catat($p, self::L_SELESAI, $sekarang, $p->pelanggan?->nama ?? $p->tujuan);
        self::susunUlang($p);
    }

    /** Langkah standar untuk pesanan baru. */
    public static function langkahAwal(): array
    {
        return [self::L_DIBUAT, self::L_MENUJU_PABRIK, self::L_MUAT, self::L_PENGIRIMAN, self::L_TIBA, self::L_SELESAI];
    }

    /**
     * Posisi terakhir truk yang masih baru (dipakai sebagai titik asal pesanan baru).
     *
     * @return array{0: float, 1: float, 2: \Illuminate\Support\Carbon}|null
     */
    public static function posisiTerakhirTruk(?int $truckId, int $maksJam = 12): ?array
    {
        if (! $truckId) {
            return null;
        }

        $l = LokasiTruk::where('truck_id', $truckId)
            ->where('waktu', '>=', now()->subHours($maksJam))
            ->orderByDesc('waktu')
            ->first();

        return $l ? [(float) $l->lat, (float) $l->lng, $l->waktu] : null;
    }

    /* ------------------------------------------------------------------ */

    /** Tandai langkah standar selesai (atau buat kalau belum ada). */
    private static function catat(Pengiriman $p, string $judul, CarbonInterface $waktu, ?string $lokasi): void
    {
        $e = $p->events()->where('judul', $judul)->where('selesai', false)->orderBy('urutan')->first();

        if ($e) {
            $e->update(['selesai' => true, 'waktu' => $waktu, 'lokasi' => $lokasi ?: $e->lokasi]);
        } elseif (! $p->events()->where('judul', $judul)->where('selesai', true)->exists()) {
            self::tambah($p, $judul, $waktu, $lokasi);
        }
    }

    private static function tambah(Pengiriman $p, string $judul, CarbonInterface $waktu, ?string $lokasi): void
    {
        $p->events()->create([
            'urutan'  => 999,
            'judul'   => $judul,
            'waktu'   => $waktu,
            'lokasi'  => $lokasi,
            'selesai' => true,
        ]);
    }

    /**
     * Urutkan ulang langkah: yang sudah selesai menurut waktu, lalu yang belum selesai
     * (urutan standarnya dipertahankan).
     */
    public static function susunUlang(Pengiriman $p): void
    {
        $semua = TrackingEvent::where('pengiriman_id', $p->id)->get();

        $selesai = $semua->where('selesai', true)
            ->sortBy(fn ($e) => [$e->waktu?->getTimestamp() ?? 0, $e->urutan, $e->id])
            ->values();
        $belum = $semua->where('selesai', false)->sortBy(fn ($e) => [$e->urutan, $e->id])->values();

        foreach ($selesai->concat($belum)->values() as $i => $e) {
            if ($e->urutan !== $i + 1) {
                $e->urutan = $i + 1;
                $e->saveQuietly();
            }
        }

        $p->unsetRelation('events');
    }

    private static function titikTujuan(Pengiriman $p): ?array
    {
        return $p->tujuan_lat !== null && $p->tujuan_lng !== null
            ? [(float) $p->tujuan_lat, (float) $p->tujuan_lng]
            : null;
    }

    private static function titikPelanggan(Pengiriman $p): ?array
    {
        $pl = $p->pelanggan;

        return $pl && $pl->lat !== null && $pl->lng !== null ? [(float) $pl->lat, (float) $pl->lng] : null;
    }

    /**
     * Truk dianggap sudah melewati pabrik bila sekarang jauh lebih dekat ke tujuan
     * dibanding pabrik ke tujuan, padahal posisi awalnya lebih jauh dari pabrik.
     */
    private static function melewatiPabrik(Pengiriman $p, array $posisi, array $pabrik, ?array $tujuan): bool
    {
        if (! $tujuan || $p->asal_lat === null) {
            return false;
        }

        $pabrikKeTujuan = self::jarak($pabrik, $tujuan);
        $asalKeTujuan = self::jarak([(float) $p->asal_lat, (float) $p->asal_lng], $tujuan);

        // Posisi awal truk sudah di antara pabrik & tujuan → tidak bisa disimpulkan
        if ($asalKeTujuan <= $pabrikKeTujuan) {
            return false;
        }

        return self::jarak($posisi, $tujuan) < $pabrikKeTujuan - max(2000, $pabrikKeTujuan * 0.1);
    }

    private static function interval(Pengiriman $p, array $posisi, ?array $pabrik, ?array $tujuan, array $atur): int
    {
        $dekat = $atur['jarak_dekat'] * 1000;

        $targetDekat = match ($p->status) {
            'menunggu', 'menuju_pabrik' => $pabrik && self::jarak($posisi, $pabrik) <= $dekat,
            'muat'                      => true,   // perlu tahu kapan keluar pabrik
            'dalam_perjalanan'          => $tujuan && self::jarak($posisi, $tujuan) <= $dekat,
            default                     => false,
        };

        return $targetDekat ? self::INTERVAL_DEKAT : self::INTERVAL_NORMAL;
    }

    /** Jarak dua titik dalam meter (haversine). */
    public static function jarak(array $a, array $b): float
    {
        $r = 6371000;
        $dLat = deg2rad($b[0] - $a[0]);
        $dLng = deg2rad($b[1] - $a[1]);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($a[0])) * cos(deg2rad($b[0])) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($h)));
    }
}
