<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'email',
        'username',
        'password',
        'disetujui',
        'disetujui_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'disetujui' => 'boolean',
            'disetujui_at' => 'datetime',
        ];
    }

    /** Pemilik website (config app.pemilik): satu-satunya yang bisa menyetujui akun baru. */
    public function isPemilik(): bool
    {
        return $this->username !== null
            && strcasecmp($this->username, (string) config('app.pemilik')) === 0
            && $this->disetujui;
    }

    /** Nama yang ditampilkan: username, atau email kalau daftar memakai email. */
    public function getLabelAttribute(): string
    {
        return $this->username ?: (string) $this->email;
    }
}
