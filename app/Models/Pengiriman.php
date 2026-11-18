<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengiriman extends Model
{
    protected $table = 'pengiriman';

    protected $fillable = [
        'no_resi', 'pelanggan_id', 'truck_id', 'tujuan', 'muatan',
        'tanggal_kirim', 'estimasi_tiba', 'status',
        'asal', 'asal_lat', 'asal_lng', 'tujuan_lat', 'tujuan_lng',
        'posisi_lat', 'posisi_lng', 'posisi_update',
        'waktu_tiba_pabrik', 'waktu_keluar_pabrik', 'waktu_tiba_tujuan',
        'lewat_pabrik',
    ];

    protected $casts = [
        'tanggal_kirim' => 'date',
        'estimasi_tiba' => 'date',
        'posisi_update' => 'datetime',
        'waktu_tiba_pabrik'   => 'datetime',
        'waktu_keluar_pabrik' => 'datetime',
        'waktu_tiba_tujuan'   => 'datetime',
        'lewat_pabrik'        => 'boolean',
    ];

    // status => [label, warna badge]
    // Urutan = alur perjalanan. Tahap menuju_pabrik s/d tiba diatur otomatis dari lokasi HP supir
    // (lihat App\Support\ProgresPengiriman).
    public const STATUS = [
        'menunggu'         => ['Menunggu',              'amber'],
        'menuju_pabrik'    => ['Menuju Pabrik',         'amber'],
        'muat'             => ['Sedang Muat di Pabrik', 'amber'],
        'dalam_perjalanan' => ['Dalam Pengiriman',      'green'],
        'tiba'             => ['Tiba di Tujuan',        'green'],
        'terkirim'         => ['Terkirim',              'blue'],
        'tertunda'         => ['Tertunda',              'red'],
    ];

    /** Langkah yang hanya ada kalau rute lewat pabrik. */
    public const LANGKAH_PABRIK = ['Menuju Pabrik', 'Sedang Muat di Pabrik'];

    /** Status yang masih berjalan (muncul di aplikasi supir). */
    public const STATUS_AKTIF = ['menunggu', 'menuju_pabrik', 'muat', 'dalam_perjalanan', 'tiba', 'tertunda'];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('urutan');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status][0] ?? ucfirst($this->status);
    }

    /**
     * Langkah standar untuk pengiriman baru.
     */
    public const LANGKAH_STANDAR = ['Pesanan Dibuat', 'Menuju Pabrik', 'Sedang Muat di Pabrik', 'Dalam Pengiriman', 'Tiba di Tujuan', 'Selesai'];

    /**
     * Buat nomor resi berikutnya: PWB-YYYYMMDD-0001.
     */
    public static function resiBaru(?\DateTimeInterface $tanggal = null): string
    {
        $prefix = 'PWB-'.($tanggal ? $tanggal->format('Ymd') : now()->format('Ymd')).'-';
        $terakhir = static::where('no_resi', 'like', $prefix.'%')->orderByDesc('no_resi')->value('no_resi');
        $nomor = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix.str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusWarnaAttribute(): string
    {
        return self::STATUS[$this->status][1] ?? 'blue';
    }

    /** "Lewat pabrik" atau "Dalam kota". */
    public function getRuteLabelAttribute(): string
    {
        return $this->lewat_pabrik ? 'Lewat pabrik (muat di pabrik)' : 'Dalam kota (langsung ke tujuan)';
    }
}
