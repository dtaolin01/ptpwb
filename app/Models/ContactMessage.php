<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected static function booted(): void
    {
        static::created(fn (ContactMessage $m) => AdminNotifikasi::create([
            'jenis' => 'pesan',
            'judul' => 'Pesan baru dari '.$m->nama,
            'isi'   => \Illuminate\Support\Str::limit($m->subjek, 120),
        ]));
    }

    protected $fillable = ['nama', 'email', 'telepon', 'subjek', 'pesan', 'dibaca'];

    protected function casts(): array
    {
        return [
            'dibaca' => 'boolean',
        ];
    }
}
