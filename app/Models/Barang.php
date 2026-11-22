<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Daftar barang tersimpan (nama, kode, satuan) untuk isian surat jalan. */
class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = ['nama', 'kode', 'satuan', 'dipakai'];

    /** Untuk isian otomatis di form: yang paling sering dipakai lebih dulu. */
    public static function katalog(): array
    {
        return static::orderByDesc('dipakai')->orderBy('nama')->limit(1000)
            ->get(['nama', 'kode', 'satuan'])
            ->map(fn ($b) => ['nama' => $b->nama, 'kode' => $b->kode, 'satuan' => $b->satuan])
            ->all();
    }

    /**
     * Simpan otomatis barang dari pengiriman ke daftar (barang baru ditambahkan,
     * kode/satuan yang masih kosong dilengkapi, hitungan "dipakai" bertambah).
     */
    public static function pelajari(?array $daftar): void
    {
        foreach ((array) $daftar as $b) {
            $nama = trim((string) ($b['nama'] ?? ''));
            if ($nama === '') {
                continue;
            }
            rescue(function () use ($b, $nama) {
                $ada = static::whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first();
                if (! $ada) {
                    static::create(['nama' => $nama, 'kode' => $b['kode'] ?? null, 'satuan' => $b['satuan'] ?? null, 'dipakai' => 1]);

                    return;
                }
                $ada->kode = $ada->kode ?: ($b['kode'] ?? null);
                $ada->satuan = $ada->satuan ?: ($b['satuan'] ?? null);
                $ada->dipakai++;
                $ada->save();
            }, report: false);
        }
    }

    /** Penjual yang pernah diisi (untuk pilihan cepat). */
    public static function daftarPenjual(): array
    {
        return Pengiriman::whereNotNull('penjual')->where('penjual', '!=', '')
            ->selectRaw('penjual, COUNT(*) as n')->groupBy('penjual')->orderByDesc('n')->limit(200)
            ->pluck('penjual')->all();
    }

    /** Penjual & barang pengiriman terakhir tiap pelanggan (tombol "Salin dari pengiriman terakhir"). */
    public static function terakhirPerPelanggan(): array
    {
        $hasil = [];
        Pengiriman::whereNotNull('barang')->orderByDesc('id')->limit(500)
            ->get(['id', 'pelanggan_id', 'penjual', 'barang'])
            ->each(function ($p) use (&$hasil) {
                $hasil[$p->pelanggan_id] ??= ['penjual' => $p->penjual, 'barang' => $p->barang];
            });

        return $hasil;
    }
}
