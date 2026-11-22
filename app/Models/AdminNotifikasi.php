<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kejadian penting untuk aplikasi PWB Admin (muncul sebagai notifikasi HP):
 *   status    : truk masuk pabrik, berangkat, tiba, pengiriman selesai
 *   pesan     : pesan baru dari formulir kontak website
 *   akun      : pendaftaran akun admin baru (hanya untuk pemilik)
 *   gps       : truk dengan pesanan aktif tidak mengirim lokasi lama
 *   terlambat : pengiriman melewati estimasi tiba
 */
class AdminNotifikasi extends Model
{
    protected $table = 'admin_notifikasi';

    protected $fillable = ['jenis', 'judul', 'isi', 'pengiriman_id', 'truck_id'];

    /** Status pengiriman yang perlu diberitahukan → [judul, ikon jenis]. */
    public const STATUS_PENTING = [
        'muat'             => 'Truk masuk pabrik',
        'dalam_perjalanan' => 'Berangkat ke tujuan',
        'tiba'             => 'Tiba di tujuan',
        'terkirim'         => 'Pengiriman selesai',
    ];

    public function pengiriman(): BelongsTo
    {
        return $this->belongsTo(Pengiriman::class);
    }

    public static function dariStatus(Pengiriman $p): void
    {
        $judul = self::STATUS_PENTING[$p->status] ?? null;
        if (! $judul) {
            return;
        }
        $p->loadMissing(['truck', 'pelanggan']);

        static::create([
            'jenis'         => 'status',
            'judul'         => $judul,
            'isi'           => collect([$p->truck?->plat, $p->no_resi, $p->pelanggan?->nama])->filter()->implode(' · '),
            'pengiriman_id' => $p->id,
            'truck_id'      => $p->truck_id,
        ]);
    }

    public function keArray(): array
    {
        return [
            'id'            => $this->id,
            'jenis'         => $this->jenis,
            'judul'         => $this->judul,
            'isi'           => $this->isi,
            'pengiriman_id' => $this->pengiriman_id,
            'waktu'         => $this->created_at?->toIso8601String(),
            'waktu_label'   => $this->created_at?->locale('id')->diffForHumans(),
        ];
    }
}
