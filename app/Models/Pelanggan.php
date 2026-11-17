<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $fillable = ['nama', 'kontak', 'email', 'alamat', 'lat', 'lng', 'kota', 'lokasi_sumber'];

    /** [lat, lng] lokasi perusahaan, atau null kalau belum ada. */
    public function titik(): ?array
    {
        return $this->lat !== null && $this->lng !== null ? [(float) $this->lat, (float) $this->lng] : null;
    }

    public function pengiriman(): HasMany
    {
        return $this->hasMany(Pengiriman::class);
    }
}
