<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kejadian penting untuk aplikasi PWB Admin (muncul sebagai notifikasi HP):
 *   status    : truk mendekati / masuk / keluar pabrik, berangkat, tiba, pengiriman selesai
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

    /** Notifikasi radius pabrik yang menggantikan notifikasi perubahan status (supaya tidak dobel). */
    private const SAMA_DENGAN_STATUS = [
        'tiba'   => 'muat',              // masuk radius pabrik  ≈ status Muat
        'keluar' => 'dalam_perjalanan',  // keluar pabrik        ≈ status Berangkat
    ];

    /** Menit: dua notifikasi untuk kejadian yang sama dalam rentang ini dianggap dobel. */
    private const RENTANG_DOBEL = 15;

    /** Truk mendekati / masuk / keluar radius pabrik (dari DeteksiPabrik), dengan atau tanpa pesanan. */
    public static function dariPabrik(string $jenis, string $judul, string $pesan, Truck $truck, ?Pengiriman $p, $waktu): void
    {
        rescue(function () use ($jenis, $judul, $pesan, $truck, $p) {
            // Sudah ada notifikasi status yang sama (mis. admin/sistem baru saja mengubah status ke Muat)
            $status = self::SAMA_DENGAN_STATUS[$jenis] ?? null;
            if ($status && static::where('truck_id', $truck->id)->where('judul', self::STATUS_PENTING[$status])
                ->where('created_at', '>=', now()->subMinutes(self::RENTANG_DOBEL))->exists()) {
                return;
            }
            $isi = $pesan.($p ? ' · '.$p->no_resi : ' · tanpa pesanan aktif');

            static::create([
                'jenis'         => 'status',
                'judul'         => $judul,
                'isi'           => mb_substr($isi, 0, 255),
                'pengiriman_id' => $p?->id,
                'truck_id'      => $truck->id,
            ]);
        }, report: false);
    }

    public static function dariStatus(Pengiriman $p): void
    {
        $judul = self::STATUS_PENTING[$p->status] ?? null;
        if (! $judul) {
            return;
        }
        // Sudah diberitahukan lewat notifikasi radius pabrik
        $judulPabrik = ['muat' => 'Truk tiba di pabrik', 'dalam_perjalanan' => 'Truk keluar dari pabrik'][$p->status] ?? null;
        if ($judulPabrik && $p->truck_id && static::where('truck_id', $p->truck_id)->where('judul', $judulPabrik)
            ->where('created_at', '>=', now()->subMinutes(self::RENTANG_DOBEL))->exists()) {
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
