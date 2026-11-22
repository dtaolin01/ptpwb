<?php

namespace App\Support;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Http;

/**
 * Kirim pesan WhatsApp otomatis lewat Fonnte (https://fonnte.com).
 * Token & tombol aktif diatur admin di dashboard: menu "WhatsApp Otomatis".
 * Untuk pindah layanan lain cukup ubah fungsi kirim() di file ini.
 */
class WhatsApp
{
    public const URL = 'https://api.fonnte.com/send';

    public static function token(): ?string
    {
        $t = trim((string) Pengaturan::ambil('wa_token', ''));

        return $t !== '' ? $t : null;
    }

    public static function aktif(): bool
    {
        return (bool) Pengaturan::ambil('wa_aktif', false) && self::token() !== null;
    }

    /** "0812-3456 789" / "+62 812…" → "628123456789", atau null kalau bukan nomor HP. */
    public static function nomor(?string $telepon): ?string
    {
        $n = preg_replace('/\D+/', '', (string) $telepon);
        if (str_starts_with($n, '0')) {
            $n = '62'.substr($n, 1);
        } elseif (str_starts_with($n, '8')) {
            $n = '62'.$n;
        }

        return strlen($n) >= 10 && strlen($n) <= 15 ? $n : null;
    }

    /** @return array{0: bool, 1: string} [berhasil, keterangan] */
    public static function kirim(string $nomor, string $pesan): array
    {
        $token = self::token();
        if (! $token) {
            return [false, 'Token Fonnte belum diisi'];
        }

        try {
            $res = Http::asForm()
                ->withHeaders(['Authorization' => $token])
                ->timeout(15)
                ->post(self::URL, [
                    'target'      => $nomor,
                    'message'     => $pesan,
                    'countryCode' => '62',
                ]);
        } catch (\Throwable $e) {
            return [false, 'Tidak bisa menghubungi Fonnte: '.mb_substr($e->getMessage(), 0, 120)];
        }

        $json = $res->json();
        if ($res->successful() && ($json['status'] ?? false)) {
            return [true, 'Terkirim'];
        }

        $alasan = $json['reason'] ?? $json['detail'] ?? ('HTTP '.$res->status());

        return [false, 'Fonnte: '.(is_string($alasan) ? $alasan : json_encode($alasan))];
    }
}
