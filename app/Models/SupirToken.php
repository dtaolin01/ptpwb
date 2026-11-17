<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupirToken extends Model
{
    protected $fillable = ['supir_id', 'truck_id', 'token', 'terakhir_dipakai'];

    protected $casts = ['terakhir_dipakai' => 'datetime'];

    public function supir(): BelongsTo
    {
        return $this->belongsTo(Supir::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }
}
