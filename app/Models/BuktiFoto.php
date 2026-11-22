<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class BuktiFoto extends Model
{
    protected $table = 'bukti_foto';

    protected $fillable = ['pengiriman_id', 'path', 'ukuran', 'lebar', 'tinggi', 'lat', 'lng', 'waktu_foto', 'alamat', 'sumber_lokasi'];

    protected $casts = ['waktu_foto' => 'datetime'];

    /** Waktu foto diambil (foto lama sebelum fitur ini: waktu upload). */
    public function waktu()
    {
        return ($this->waktu_foto ?? $this->created_at)?->copy()->setTimezone(config('app.timezone'));
    }

    public function waktuLabel(): ?string
    {
        return $this->waktu()?->locale('id')->translatedFormat('l, d M Y · H.i').' '.\App\Support\FotoBukti::zona();
    }

    public function linkPeta(): ?string
    {
        return $this->lat !== null ? 'https://www.google.com/maps?q='.$this->lat.','.$this->lng : null;
    }

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
            'waktu'       => $this->waktu()?->toIso8601String(),
            'waktu_label' => $this->waktuLabel(),
            'alamat'      => $this->alamat,
            'lat'         => $this->lat !== null ? (float) $this->lat : null,
            'lng'         => $this->lng !== null ? (float) $this->lng : null,
            'peta'        => $this->linkPeta(),
        ];
    }
}
