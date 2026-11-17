<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckerToken extends Model
{
    protected $fillable = ['checker_id', 'token', 'terakhir_dipakai'];

    protected $casts = ['terakhir_dipakai' => 'datetime'];

    public function checker(): BelongsTo
    {
        return $this->belongsTo(Checker::class);
    }
}
