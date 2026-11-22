<?php

namespace App\Support;

use App\Models\BuktiFoto;
use App\Models\LokasiTruk;
use App\Models\Pengiriman;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyimpan foto bukti pengiriman sehemat mungkin:
 *  - diperkecil ke sisi terpanjang 1280 px (cukup jelas untuk bukti, tetap ringan)
 *  - disimpan ulang sebagai WebP kualitas 70 (≈ 60–150 KB per foto; JPEG bila server tidak mendukung WebP)
 *  - data EXIF (lokasi kamera, dll.) ikut terbuang
 *  - file di storage (bukan database), maksimal MAKS foto per pengiriman
 *  - keterangan (hari, tanggal, jam, alamat, koordinat, resi, plat, sopir) dicetak di bagian bawah foto
 */
class FotoBukti
{
    public const MAKS = 5;
    public const SISI = 1280;
    public const KUALITAS = 70;

    /**
     * @param  CarbonInterface|null  $waktu  kapan foto diambil (dari HP); null = sekarang
     */
    public static function simpan(Pengiriman $p, UploadedFile $file, ?float $lat = null, ?float $lng = null, ?CarbonInterface $waktu = null): BuktiFoto
    {
        $sumber = $file->getRealPath();
        $gambar = @imagecreatefromstring((string) file_get_contents($sumber));
        if (! $gambar) {
            throw new \RuntimeException('File bukan gambar yang bisa dibaca.');
        }

        // --- Keterangan: waktu & lokasi ---
        $waktu = ($waktu ?? now())->copy()->setTimezone(config('app.timezone'));
        $sumberLokasi = $lat !== null && $lng !== null ? 'hp' : null;
        if (! $sumberLokasi && $p->truck_id) {
            // HP tidak mengirim lokasi → pakai posisi terakhir truk (≤ 30 menit dari waktu foto)
            $l = LokasiTruk::where('truck_id', $p->truck_id)
                ->whereBetween('waktu', [$waktu->copy()->subMinutes(30), $waktu->copy()->addMinutes(5)])
                ->orderByDesc('waktu')->first();
            if ($l) {
                [$lat, $lng, $sumberLokasi] = [(float) $l->lat, (float) $l->lng, 'truk'];
            }
        }
        $alamat = $lat !== null ? Geocoder::alamatLengkap($lat, $lng) : null;

        $gambar = self::putarSesuaiExif($gambar, $sumber);
        [$w, $h] = [imagesx($gambar), imagesy($gambar)];
        $skala = min(1, self::SISI / max($w, $h));
        if ($skala < 1) {
            $nw = max(1, (int) round($w * $skala));
            $nh = max(1, (int) round($h * $skala));
            $kecil = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($kecil, $gambar, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($gambar);
            [$gambar, $w, $h] = [$kecil, $nw, $nh];
        }

        $p->loadMissing('truck.supir');
        self::cap($gambar, self::barisKeterangan($p, $waktu, $alamat, $lat, $lng));

        $webp = function_exists('imagewebp');
        ob_start();
        $webp ? imagewebp($gambar, null, self::KUALITAS) : imagejpeg($gambar, null, self::KUALITAS);
        $isi = ob_get_clean();
        imagedestroy($gambar);

        $path = 'bukti/'.now()->format('Y/m').'/'.Str::slug($p->no_resi).'-'.Str::random(10).($webp ? '.webp' : '.jpg');
        Storage::disk('local')->put($path, $isi);

        return BuktiFoto::create([
            'pengiriman_id' => $p->id,
            'path'          => $path,
            'ukuran'        => strlen($isi),
            'lebar'         => $w,
            'tinggi'        => $h,
            'lat'           => $lat,
            'lng'           => $lng,
            'waktu_foto'    => $waktu,
            'alamat'        => $alamat,
            'sumber_lokasi' => $sumberLokasi,
        ]);
    }

    /** "WITA" / "WIB" / "WIT" sesuai zona waktu website. */
    public static function zona(): string
    {
        return ['Asia/Makassar' => 'WITA', 'Asia/Jakarta' => 'WIB', 'Asia/Pontianak' => 'WIB', 'Asia/Jayapura' => 'WIT'][config('app.timezone')] ?? now()->format('T');
    }

    /** @return array<int, array{0: string, 1: bool}> [teks, tebal] */
    private static function barisKeterangan(Pengiriman $p, CarbonInterface $waktu, ?string $alamat, ?float $lat, ?float $lng): array
    {
        $baris = [[$waktu->locale('id')->translatedFormat('l, d F Y').'  •  '.$waktu->format('H.i').' '.self::zona(), true]];
        $baris[] = [$alamat ?: ($lat !== null ? 'Alamat tidak ditemukan' : 'Lokasi tidak tersedia'), false];
        $info = array_filter([
            $lat !== null ? number_format($lat, 6, '.', '').', '.number_format($lng, 6, '.', '') : null,
            $p->no_resi,
            $p->truck?->plat,
            $p->truck?->supir?->nama,
        ]);
        $baris[] = [implode('  •  ', $info), false];

        return $baris;
    }

    /** Cetak keterangan di bagian bawah foto (latar gelap transparan, teks putih). */
    private static function cap(\GdImage $g, array $baris): void
    {
        $w = imagesx($g);
        $h = imagesy($g);
        $font = resource_path('fonts/DejaVuSans.ttf');
        $tebal = resource_path('fonts/DejaVuSans-Bold.ttf');
        $ttf = function_exists('imagettftext') && is_file($font);

        $ukuran = max(11, (int) round(max($w, $h) / 58)); // ±22 px pada foto 1280 px (mendatar maupun berdiri)
        $jarak = (int) round($ukuran * 1.55);
        $tepi = (int) round($ukuran * 0.9);
        $lebarMaks = $w - 2 * $tepi;

        // Bungkus baris panjang (alamat) supaya tidak terpotong
        $siap = [];
        foreach ($baris as [$teks, $b]) {
            foreach ($ttf ? self::bungkus($teks, $b ? $tebal : $font, $b ? $ukuran : $ukuran * 0.9, $lebarMaks) : str_split($teks, max(10, (int) ($lebarMaks / 7))) as $potong) {
                $siap[] = [$potong, $b];
            }
        }

        $tinggiKotak = count($siap) * $jarak + $tepi;
        $hitam = imagecolorallocatealpha($g, 0, 0, 0, 50);
        imagealphablending($g, true);
        imagefilledrectangle($g, 0, $h - $tinggiKotak, $w, $h, $hitam);
        $putih = imagecolorallocate($g, 255, 255, 255);
        $kuning = imagecolorallocate($g, 255, 214, 10);

        $y = $h - $tinggiKotak + $jarak;
        foreach ($siap as $i => [$teks, $b]) {
            if ($ttf) {
                imagettftext($g, $b ? $ukuran : $ukuran * 0.9, 0, $tepi, $y - (int) ($jarak * 0.3), $i === 0 ? $kuning : $putih, $b ? $tebal : $font, $teks);
            } else {
                imagestring($g, 4, $tepi, $y - $jarak + 4, $teks, $i === 0 ? $kuning : $putih);
            }
            $y += $jarak;
        }
    }

    private static function bungkus(string $teks, string $font, float $ukuran, int $lebarMaks): array
    {
        $hasil = [];
        $baris = '';
        foreach (preg_split('/\s+/', trim($teks)) as $kata) {
            $coba = $baris === '' ? $kata : $baris.' '.$kata;
            $kotak = imagettfbbox($ukuran, 0, $font, $coba);
            if ($baris !== '' && ($kotak[2] - $kotak[0]) > $lebarMaks) {
                $hasil[] = $baris;
                $baris = $kata;
            } else {
                $baris = $coba;
            }
        }
        if ($baris !== '') {
            $hasil[] = $baris;
        }

        return $hasil ?: [''];
    }

    /** Foto dari HP sering tersimpan miring + tag orientasi; luruskan dulu. */
    private static function putarSesuaiExif(\GdImage $g, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $g;
        }
        $o = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        $sudut = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($sudut === 0) {
            return $g;
        }
        $baru = imagerotate($g, $sudut, 0);
        if ($baru) {
            imagedestroy($g);

            return $baru;
        }

        return $g;
    }
}
