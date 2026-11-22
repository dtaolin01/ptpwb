<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Models\AdminNotifikasi;
use App\Support\SuratJalan;
use Illuminate\Http\Request;

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

    /** Penerima menandatangani surat jalan secara online (dari link WhatsApp). */
    public function tandaTangan(Request $request, Pengiriman $pengiriman)
    {
        if ($pengiriman->ttd_at) {
            return back()->with('info', 'Surat jalan ini sudah ditandatangani.');
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'ttd'  => ['required', 'string', 'max:400000', 'starts_with:data:image/png;base64,'],
        ], ['ttd.required' => 'Silakan buat tanda tangan dulu.'], ['nama' => 'nama penerima']);

        $png = base64_decode(substr($data['ttd'], strlen('data:image/png;base64,')), true);
        $info = $png ? @getimagesizefromstring($png) : false;
        if (! $info || $info[2] !== IMAGETYPE_PNG) {
            return back()->withErrors(['ttd' => 'Tanda tangan tidak terbaca, silakan ulangi.'])->withInput();
        }

        try {
            $pengiriman->forceFill([
                'ttd_nama'   => trim($data['nama']),
                'ttd_gambar' => $data['ttd'],
                'ttd_at'     => now(),
                'ttd_ip'     => $request->ip(),
            ])->saveQuietly();
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput($request->except('ttd'))
                ->withErrors(['ttd' => 'Maaf, tanda tangan belum bisa disimpan. Silakan coba lagi nanti atau hubungi PT Putra Wijaya Berjaya.']);
        }

        rescue(fn () => AdminNotifikasi::create([
            'jenis'         => 'status',
            'judul'         => 'Surat jalan ditandatangani',
            'isi'           => $pengiriman->no_resi.' · diterima oleh '.trim($data['nama']),
            'pengiriman_id' => $pengiriman->id,
            'truck_id'      => $pengiriman->truck_id,
        ]));

        return back()->with('success', 'Terima kasih, surat jalan sudah ditandatangani.');
    }

    /** Admin menghapus tanda tangan (mis. salah orang) supaya bisa ditandatangani ulang. */
    public function hapusTandaTangan(Pengiriman $pengiriman)
    {
        $pengiriman->forceFill(['ttd_nama' => null, 'ttd_gambar' => null, 'ttd_at' => null, 'ttd_ip' => null])->saveQuietly();

        return back()->with('success', 'Tanda tangan penerima dihapus.');
    }

    private function tampil(Pengiriman $p, bool $admin)
    {
        $p->loadMissing(['pelanggan', 'truck.supir', 'buktiFoto']);

        return view('suratJalan', [
            'p'        => $p,
            'barang'   => SuratJalan::barang($p),
            'tracking' => route('tracking', ['resi' => $p->no_resi]),
            'admin'    => $admin,
            'urlTtd'   => SuratJalan::linkTandaTangan($p),
        ]);
    }
}
