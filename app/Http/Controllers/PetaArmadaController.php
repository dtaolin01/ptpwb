<?php

namespace App\Http\Controllers;

use App\Models\LokasiTruk;
use App\Models\Pengiriman;
use App\Models\Truck;
use App\Support\DeteksiPabrik;
use App\Support\Pabrik;
use App\Support\ProgresPengiriman;

/**
 * Halaman admin "Peta Armada": semua truk di peta dengan lokasi terakhir dari HP supir.
 * Arahkan pointer (atau ketuk di HP) ke ikon truk untuk melihat info armadanya.
 */
class PetaArmadaController extends Controller
{
    /** Lokasi lebih lama dari ini ditandai "lama" (abu-abu). HP mengirim tiap 30 menit. */
    /** Lokasi dianggap lama kalau lebih dari ini (menit). Aplikasi supir mengirim tiap ±1 menit saat jalan, tiap 15 menit saat diam. */
    private const MENIT_LAMA = 30;

    public function index()
    {
        return view('petaArmada', ['peta' => $this->susun()]);
    }

    /** Dipanggil halaman tiap 60 detik untuk memperbarui posisi truk. */
    public function data()
    {
        return response()->json($this->susun());
    }

    public function susun(): array
    {
        $atur = Pabrik::data();
        $pabrik = Pabrik::titik();

        $trucks = Truck::with('supir')->orderBy('plat')->get();

        // Lokasi terakhir tiap truk
        $lokasi = LokasiTruk::whereIn('truck_id', $trucks->pluck('id'))
            ->orderByDesc('waktu')
            ->orderByDesc('id')
            ->get()
            ->unique('truck_id')
            ->keyBy('truck_id');

        // Pesanan aktif tiap truk
        $pesanan = Pengiriman::with('pelanggan')
            ->whereIn('truck_id', $trucks->pluck('id'))
            ->whereIn('status', Pengiriman::STATUS_AKTIF)
            ->orderByDesc('tanggal_kirim')
            ->orderByDesc('id')
            ->get()
            ->unique('truck_id')
            ->keyBy('truck_id');

        $daftar = $trucks->map(function (Truck $t) use ($lokasi, $pesanan, $pabrik, $atur) {
            $l = $lokasi->get($t->id);
            $p = $pesanan->get($t->id);

            // Belum ada lokasi dari aplikasi → pakai posisi yang diisi admin di pesanan (kalau ada)
            $lat = $l?->lat ?? $p?->posisi_lat;
            $lng = $l?->lng ?? $p?->posisi_lng;
            $waktu = $l?->waktu ?? $p?->posisi_update;

            $jarak = ($pabrik && $lat !== null) ? ProgresPengiriman::jarak([(float) $lat, (float) $lng], $pabrik) : null;
            $zona = match (true) {
                $jarak === null                          => null,
                $jarak <= $atur['radius_pabrik']         => 'Di area pabrik',
                $jarak <= $atur['jarak_dekat'] * 1000    => 'Mendekati pabrik (±'.DeteksiPabrik::km($jarak).')',
                default                                  => null,
            };

            return [
                'id'          => $t->id,
                'plat'        => $t->plat,
                'jenis'       => $t->jenis,
                'kapasitas'   => $t->kapasitas_ton.' ton',
                'status'      => $t->status_label,
                'foto'        => $t->foto ? asset('images/'.$t->foto) : null,
                'supir'       => $t->supir?->nama ?? 'Belum ada supir',
                'telepon'     => $t->supir?->telepon,
                'lat'         => $lat !== null ? (float) $lat : null,
                'lng'         => $lng !== null ? (float) $lng : null,
                'waktu'       => $waktu?->locale('id')->translatedFormat('d M Y, H.i'),
                'waktu_relatif' => $waktu?->locale('id')->diffForHumans(),
                'lama'        => $waktu ? $waktu->lt(now()->subMinutes(self::MENIT_LAMA)) : true,
                'zona'        => $zona,
                'status_kode' => $t->status,
                'pesanan'     => $p ? [
                    'id'        => $p->id,
                    'no_resi'   => $p->no_resi,
                    'status'    => $p->status_label,
                    'warna'     => $p->status_warna,
                    'muatan'    => $p->muatan,
                    'pelanggan' => $p->pelanggan?->nama,
                    'rute'      => ($p->asal ?: '-').' → '.$p->tujuan,
                    'url'       => route('trackingAdmin', ['resi' => $p->no_resi]),
                ] : null,
            ];
        })->values();

        return [
            'pabrik' => $pabrik ? [
                'nama'   => $atur['nama'],
                'lat'    => $pabrik[0],
                'lng'    => $pabrik[1],
                'radius' => $atur['radius_pabrik'],
            ] : null,
            'truk'       => $daftar,
            'diperbarui' => now()->locale('id')->translatedFormat('H.i'),
        ];
    }
}
