<?php

namespace App\Support;

use App\Models\Pengiriman;
use Illuminate\Support\Facades\URL;

/**
 * Surat jalan: halaman cetak (A4) + pengiriman otomatis ke WhatsApp pelanggan
 * saat pengiriman dibuat.
 */
class SuratJalan
{
    public static function nomor(Pengiriman $p): string
    {
        return 'SJ/'.$p->no_resi;
    }

    /** Link untuk pelanggan (tanpa login, tidak kedaluwarsa, tidak bisa diubah ke pengiriman lain). */
    public static function link(Pengiriman $p): string
    {
        return url(URL::signedRoute('suratJalan.publik', ['pengiriman' => $p->id], null, false));
    }

    public static function pesan(Pengiriman $p): string
    {
        $p->loadMissing(['pelanggan', 'truck.supir']);
        $baris = [
            'Yth. '.($p->pelanggan?->kontak ?: $p->pelanggan?->nama ?: 'Bapak/Ibu').',',
            '',
            'Berikut *Surat Jalan* pengiriman dari *PT Putra Wijaya Berjaya*:',
            '',
            '• No. Surat Jalan: '.self::nomor($p),
            '• No. Resi: '.$p->no_resi,
            '• Muatan: '.$p->muatan,
            '• Tujuan: '.($p->tujuan ?: '-'),
        ];
        if ($p->truck) {
            $baris[] = '• Armada: '.$p->truck->plat.($p->truck->supir ? ' ('.$p->truck->supir->nama.')' : '');
        }
        if ($p->estimasi_tiba) {
            $baris[] = '• Estimasi tiba: '.$p->estimasi_tiba->locale('id')->translatedFormat('j F Y');
        }
        array_push($baris,
            '',
            'Lihat / cetak surat jalan:',
            self::link($p),
            '',
            'Lacak posisi truk secara langsung:',
            route('tracking', ['resi' => $p->no_resi]),
            '',
            'Terima kasih.',
            '_Pesan otomatis — PT Putra Wijaya Berjaya_'
        );

        return implode("\n", $baris);
    }

    /**
     * Kirim surat jalan ke WhatsApp pelanggan dan catat hasilnya di pengiriman.
     * $paksa = true untuk tombol "Kirim ulang" (tetap dikirim walau fitur otomatis dimatikan).
     *
     * @return array{0: bool, 1: string}
     */
    public static function kirimKePelanggan(Pengiriman $p, bool $paksa = false): array
    {
        if (! $paksa && ! WhatsApp::aktif()) {
            return [false, 'WhatsApp otomatis belum diaktifkan'];
        }
        if (! WhatsApp::token()) {
            return self::catat($p, false, 'Token Fonnte belum diisi (menu WhatsApp Otomatis)');
        }

        $p->loadMissing('pelanggan');
        $nomor = WhatsApp::nomor($p->pelanggan?->telepon);
        if (! $nomor) {
            return self::catat($p, false, 'Nomor WhatsApp pelanggan belum diisi / tidak valid');
        }

        [$ok, $ket] = WhatsApp::kirim($nomor, self::pesan($p));

        return self::catat($p, $ok, $ok ? 'Terkirim ke '.$p->pelanggan->telepon : $ket);
    }

    /** @return array{0: bool, 1: string} */
    private static function catat(Pengiriman $p, bool $ok, string $ket): array
    {
        $p->forceFill([
            'surat_jalan_wa_status' => mb_substr($ket, 0, 255),
            'surat_jalan_wa_at'     => $ok ? now() : $p->surat_jalan_wa_at,
        ])->saveQuietly();

        return [$ok, $ket];
    }
}
