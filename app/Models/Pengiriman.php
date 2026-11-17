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
    ];

    protected $casts = [
        'tanggal_kirim' => 'date',
        'estimasi_tiba' => 'date',
        'posisi_update' => 'datetime',
    ];

    // status => [label, warna badge]
    public const STATUS = [
        'menunggu'         => ['Menunggu',         'amber'],
        'dalam_perjalanan' => ['Dalam Perjalanan', 'green'],
        'terkirim'         => ['Terkirim',         'blue'],
        'tertunda'         => ['Tertunda',         'red'],
    ];

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
    public const LANGKAH_STANDAR = ['Pengiriman Diterima', 'Dalam Perjalanan', 'Menuju Tujuan', 'Tiba di Tujuan'];

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
}
