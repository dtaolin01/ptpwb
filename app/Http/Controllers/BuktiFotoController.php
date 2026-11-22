<?php

namespace App\Http\Controllers;

use App\Models\BuktiFoto;
use Illuminate\Support\Facades\Storage;

class BuktiFotoController extends Controller
{
    /** Gambar bukti (link bertanda tangan: dari aplikasi, surat jalan pelanggan, dan dashboard). */
    public function tampil(BuktiFoto $foto)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($foto->path), 404);

        return response()->file($disk->path($foto->path), [
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    public function hapus(BuktiFoto $foto)
    {
        $resi = $foto->pengiriman?->no_resi;
        $foto->delete();

        return back()->with('success', 'Foto bukti '.$resi.' dihapus.');
    }
}
