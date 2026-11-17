<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiPabrik extends Model
{
    protected $table = 'notifikasi_pabrik';

    protected $fillable = ['jenis', 'truck_id', 'pengiriman_id', 'plat', 'nama_supir', 'judul', 'pesan', 'jarak_m', 'waktu'];

    protected $casts = ['waktu' => 'datetime'];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }
}
