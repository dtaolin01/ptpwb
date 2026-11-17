<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['nama', 'email', 'telepon', 'subjek', 'pesan', 'dibaca'];

    protected function casts(): array
    {
        return [
            'dibaca' => 'boolean',
        ];
    }
}
