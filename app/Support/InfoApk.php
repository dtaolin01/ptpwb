<?php

namespace App\Support;

use ZipArchive;

/**
 * Membaca package, versionCode, dan versionName langsung dari file APK
 * (AndroidManifest.xml dalam format XML biner Android), supaya versi yang tercatat
 * di website selalu sama dengan versi aplikasi yang sebenarnya.
 */
class InfoApk
{
    private const ID_VERSION_CODE = 0x0101021B;
    private const ID_VERSION_NAME = 0x0101021C;

    /** @return array{package: ?string, version_code: ?int, version_name: ?string}|null */
    public static function baca(string $path): ?array
    {
        if (! class_exists(ZipArchive::class)) {
            return null;
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return null;
        }
        $data = $zip->getFromName('AndroidManifest.xml');
        $zip->close();

        if (! is_string($data) || strlen($data) < 16) {
            return null;
        }

        try {
            return self::urai($data);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function urai(string $d): ?array
    {
        $u16 = fn (int $o) => unpack('v', $d, $o)[1];
        $u32 = fn (int $o) => unpack('V', $d, $o)[1];
        $len = strlen($d);

        // ---- string pool ----
        $pos = 8;
        if ($u16($pos) !== 0x0001) {
            return null;
        }
        $poolSize = $u32($pos + 4);
        $jumlah = $u32($pos + 8);
        $utf8 = ($u32($pos + 16) & 0x100) !== 0;
        $mulaiString = $u32($pos + 20);

        $teks = [];
        for ($i = 0; $i < $jumlah; $i++) {
            $p = $pos + $mulaiString + $u32($pos + 28 + 4 * $i);
            if ($utf8) {
                $p += (ord($d[$p]) & 0x80) ? 2 : 1;           // panjang karakter (dilewati)
                $n = ord($d[$p]);
                if ($n & 0x80) {
                    $n = (($n & 0x7F) << 8) | ord($d[$p + 1]);
                    $p += 2;
                } else {
                    $p += 1;
                }
                $teks[] = substr($d, $p, $n);
            } else {
                $n = $u16($p);
                $p += 2;
                if ($n & 0x8000) {
                    $n = (($n & 0x7FFF) << 16) | $u16($p);
                    $p += 2;
                }
                $teks[] = mb_convert_encoding(substr($d, $p, $n * 2), 'UTF-8', 'UTF-16LE');
            }
        }
        $pos += $poolSize;

        // ---- peta resource id atribut ----
        $resId = [];
        if ($pos + 8 <= $len && $u16($pos) === 0x0180) {
            $ukuran = $u32($pos + 4);
            for ($i = 0; $i < ($ukuran - 8) / 4; $i++) {
                $resId[] = $u32($pos + 8 + 4 * $i);
            }
            $pos += $ukuran;
        }

        // ---- cari elemen <manifest> ----
        while ($pos + 8 <= $len) {
            $tipe = $u16($pos);
            $ukuran = $u32($pos + 4);
            if ($ukuran <= 0) {
                break;
            }

            if ($tipe === 0x0102 && ($teks[$u32($pos + 20)] ?? null) === 'manifest') {
                $hasil = ['package' => null, 'version_code' => null, 'version_name' => null];
                $jumlahAtr = $u16($pos + 28);
                $awalAtr = $pos + 16 + $u16($pos + 24);

                for ($i = 0; $i < $jumlahAtr; $i++) {
                    $a = $awalAtr + $i * 20;
                    $ni = $u32($a + 4);
                    $raw = $u32($a + 8);
                    $nilai = $u32($a + 16);
                    $id = $resId[$ni] ?? null;
                    $nama = $teks[$ni] ?? '';
                    $str = $raw !== 0xFFFFFFFF ? ($teks[$raw] ?? null) : null;

                    if ($id === self::ID_VERSION_CODE || $nama === 'versionCode') {
                        $hasil['version_code'] = $str !== null ? (int) $str : $nilai;
                    } elseif ($id === self::ID_VERSION_NAME || $nama === 'versionName') {
                        $hasil['version_name'] = $str ?? (string) $nilai;
                    } elseif ($nama === 'package') {
                        $hasil['package'] = $str;
                    }
                }

                return $hasil;
            }
            $pos += $ukuran;
        }

        return null;
    }
}
