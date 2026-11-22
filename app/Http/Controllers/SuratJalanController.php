<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Support\SuratJalan;

/** Surat jalan: dilihat admin, dibuka pelanggan dari link WhatsApp, dan kirim ulang ke WhatsApp. */
class SuratJalanController extends Controller
{
    /** Dibuka pelanggan dari link di WhatsApp (link bertanda tangan, tanpa login). */
    public function publik(Pengiriman $pengiriman)
    {
        return $this->tampil($pengiriman, false);
    }

    /** Dibuka admin dari dashboard. */
    public function lihat(Pengiriman $pengiriman)
    {
        return $this->tampil($pengiriman, true);
    }

    public function kirim(Pengiriman $pengiriman)
    {
        [$ok, $ket] = SuratJalan::kirimKePelanggan($pengiriman, paksa: true);

        return back()->with($ok ? 'success' : 'error', ($ok ? 'Surat jalan '.$pengiriman->no_resi.': ' : 'Surat jalan gagal dikirim: ').$ket);
    }

    private function tampil(Pengiriman $p, bool $admin)
    {
        $p->loadMissing(['pelanggan', 'truck.supir']);

        return view('suratJalan', [
            'p'        => $p,
            'barang'   => SuratJalan::barang($p),
            'tracking' => route('tracking', ['resi' => $p->no_resi]),
            'admin'    => $admin,
        ]);
    }
}
