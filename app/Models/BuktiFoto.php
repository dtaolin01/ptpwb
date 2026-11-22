<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class BuktiFoto extends Model
{
    protected $table = 'bukti_foto';

    protected $fillable = ['pengiriman_id', 'path', 'ukuran', 'lebar', 'tinggi', 'lat', 'lng'];

    protected static function booted(): void
    {
        // Hapus file-nya juga supaya tidak menumpuk di hosting
        static::deleting(fn (BuktiFoto $f) => Storage::disk('local')->delete($f->path));
    }

    public function pengiriman()
    {
        return $this->belongsTo(Pengiriman::class);
    }

    /** Link gambar tanpa login (bertanda tangan, tidak bisa ditebak). */
    public function url(): string
    {
        return url(URL::signedRoute('bukti.foto', ['foto' => $this->id], null, false));
    }

    public function keArray(): array
    {
        return [
            'id'          => $this->id,
            'url'         => $this->url(),
            'ukuran_kb'   => (int) round($this->ukuran / 1024),
            'waktu'       => $this->created_at?->toIso8601String(),
            'waktu_label' => $this->created_at?->locale('id')->translatedFormat('j M Y, H.i'),
        ];
    }
}
