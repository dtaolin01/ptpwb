<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LokasiTruk extends Model
{
    protected $table = 'lokasi_truk';

    protected $fillable = ['truck_id', 'pengiriman_id', 'lat', 'lng', 'akurasi', 'waktu'];

    protected $casts = ['waktu' => 'datetime'];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }
}
