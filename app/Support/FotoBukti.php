<?php

namespace App\Support;

use App\Models\BuktiFoto;
use App\Models\Pengiriman;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyimpan foto bukti pengiriman sehemat mungkin:
 *  - diperkecil ke sisi terpanjang 1280 px (cukup jelas untuk bukti, tetap ringan)
 *  - disimpan ulang sebagai WebP kualitas 70 (≈ 60–150 KB per foto; JPEG bila server tidak mendukung WebP)
 *  - data EXIF (lokasi kamera, dll.) ikut terbuang
 *  - file di storage (bukan database), maksimal MAKS foto per pengiriman
 */
class FotoBukti
{
    public const MAKS = 5;
    public const SISI = 1280;
    public const KUALITAS = 70;

    public static function simpan(Pengiriman $p, UploadedFile $file, ?float $lat = null, ?float $lng = null): BuktiFoto
    {
        $sumber = $file->getRealPath();
        $gambar = @imagecreatefromstring((string) file_get_contents($sumber));
        if (! $gambar) {
            throw new \RuntimeException('File bukan gambar yang bisa dibaca.');
        }

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
        ]);
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
