<?php

namespace App\Support;

use App\Models\Pengiriman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Surat jalan: halaman cetak (A4) + pengiriman otomatis ke WhatsApp pelanggan
 * saat pengiriman dibuat.
 */
class SuratJalan
{
    /**
     * Rapikan isian penjual & barang sebelum divalidasi:
     * baris barang yang kosong dibuang, dan "Jenis muatan" diisi otomatis dari nama barang bila dikosongkan.
     */
    public static function siapkanInput(Request $request): void
    {
        if (! $request->has('barang')) {
            return;
        }
        $barang = collect((array) $request->input('barang'))
            ->map(fn ($b) => array_map(fn ($v) => is_string($v) ? trim($v) : $v, (array) $b))
            ->filter(fn ($b) => filled($b['nama'] ?? null) || filled($b['kode'] ?? null) || filled($b['qty'] ?? null))
            ->map(fn ($b) => ['nama' => $b['nama'] ?? null, 'kode' => $b['kode'] ?? null, 'qty' => isset($b['qty']) ? str_replace(',', '.', (string) $b['qty']) : null, 'satuan' => $b['satuan'] ?? null])
            ->values()->all();
        $request->merge(['barang' => $barang]);

        if (blank($request->input('muatan')) && $barang) {
            $request->merge(['muatan' => mb_substr(collect($barang)->pluck('nama')->filter()->implode(', ') ?: 'Barang', 0, 150)]);
        }
    }

    /** Aturan validasi penjual & barang (dipakai website dan API admin). */
    public static function aturan(): array
    {
        return [
            'penjual'         => ['nullable', 'string', 'max:150'],
            'barang'          => ['nullable', 'array', 'max:30'],
            'barang.*.nama'   => ['required', 'string', 'max:150'],
            'barang.*.kode'   => ['nullable', 'string', 'max:50'],
            'barang.*.qty'    => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'barang.*.satuan' => ['nullable', 'string', 'max:20'],
        ];
    }

    public const PESAN = [
        'barang.*.nama.required' => 'Nama barang wajib diisi.',
        'barang.*.qty.numeric'   => 'Qty harus berupa angka.',
    ];

    /** Daftar barang untuk dicetak; pengiriman lama tanpa daftar barang memakai "Jenis muatan". */
    public static function barang(Pengiriman $p): array
    {
        return $p->barang ?: [['nama' => $p->muatan, 'kode' => null, 'qty' => null, 'satuan' => null]];
    }

    /** 25.0 → "25", 2.5 → "2,5" */
    public static function angka($qty): string
    {
        if ($qty === null || $qty === '') {
            return '';
        }
        $f = (float) $qty;

        return rtrim(rtrim(number_format($f, 3, ',', '.'), '0'), ',');
    }

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
            'Berikut *Surat Jalan* dari *PT Putra Wijaya Berjaya*:',
            '',
            '• No. Resi: '.$p->no_resi,
            '• Tanggal: '.($p->tanggal_kirim ?? now())->locale('id')->translatedFormat('j F Y'),
        ];
        if ($p->penjual) {
            $baris[] = '• Penjual: '.$p->penjual;
        }
        $baris[] = '• Barang:';
        foreach (self::barang($p) as $b) {
            $jumlah = trim(self::angka($b['qty'] ?? null).' '.($b['satuan'] ?? ''));
            $baris[] = '   - '.$b['nama'].(! empty($b['kode']) ? ' ['.$b['kode'].']' : '').($jumlah !== '' ? ' — '.$jumlah : '');
        }
        if ($p->truck) {
            $baris[] = '• Sopir / Plat: '.($p->truck->supir?->nama ?? '-').' / '.$p->truck->plat;
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
