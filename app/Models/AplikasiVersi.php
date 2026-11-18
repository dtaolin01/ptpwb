<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AplikasiVersi extends Model
{
    protected $table = 'aplikasi_versi';

    /** Kode → nama aplikasi Android. */
    public const APLIKASI = [
        'supir'   => 'PWB Supir',
        'checker' => 'PWB Checker',
    ];

    protected $fillable = ['aplikasi', 'version_code', 'version_name', 'catatan', 'wajib', 'file', 'ukuran', 'sha256'];

    protected $casts = [
        'version_code' => 'integer',
        'wajib'        => 'boolean',
        'ukuran'       => 'integer',
    ];

    public static function terbaru(string $aplikasi): ?self
    {
        return static::where('aplikasi', $aplikasi)->orderByDesc('version_code')->first();
    }

    public function getUkuranLabelAttribute(): string
    {
        return number_format($this->ukuran / 1048576, 1, ',', '.').' MB';
    }

    public function getNamaFileAttribute(): string
    {
        return 'pwb-'.$this->aplikasi.'-'.$this->version_name.'.apk';
    }
}
