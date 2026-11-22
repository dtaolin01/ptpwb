<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sesi login aplikasi Android PWB Admin. */
class AdminToken extends Model
{
    protected $fillable = ['user_id', 'token', 'perangkat', 'terakhir_dipakai'];

    protected $casts = ['terakhir_dipakai' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
