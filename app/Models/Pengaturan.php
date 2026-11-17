<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan sederhana (kunci => nilai), mis. lokasi pabrik.
 */
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'kunci';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['kunci', 'nilai'];

    public static function ambil(string $kunci, mixed $default = null): mixed
    {
        return static::find($kunci)?->nilai ?? $default;
    }

    public static function simpan(string $kunci, mixed $nilai): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai === null ? null : (string) $nilai]);
    }
}
