<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Petugas checker di pabrik (login aplikasi PWB Checker dengan telepon + PIN). */
class Checker extends Model
{
    protected $table = 'checker';

    protected $fillable = ['nama', 'telepon', 'pin', 'aktif'];

    protected $hidden = ['pin'];

    protected $casts = [
        'pin'   => 'hashed',
        'aktif' => 'boolean',
    ];

    public function tokens(): HasMany
    {
        return $this->hasMany(CheckerToken::class);
    }
}
