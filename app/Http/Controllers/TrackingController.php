<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Support\PetaPengiriman;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Halaman publik /tracking. Data diambil dari tabel pengiriman + tracking_events,
     * jadi perubahan dari admin (menu Tracking) langsung tampil di sini.
     */
    public function index(Request $request)
    {
        $resi = strtoupper(trim((string) $request->query('resi', '')));
        $shipment = null;
        $notFound = false;

        if ($resi !== '') {
            $found = Pengiriman::with(['pelanggan', 'truck', 'events'])
                ->where('no_resi', $resi)
                ->first();

            if ($found) {
                $shipment = $this->buildShipment($found);
            } else {
                $notFound = true;
            }
        }

        return view('tracking', [
            'resi'     => $resi,
            'shipment' => $shipment,
            'notFound' => $notFound,
            'whatsapp' => config('tracking.whatsapp'),
        ]);
    }

    /**
     * Mengubah data dari database menjadi bentuk yang dibaca oleh tampilan.
     */
    private function buildShipment(Pengiriman $p): array
    {
        [$etaText, $etaNote] = $this->buildEta($p);

        // Warna badge admin -> warna badge halaman publik
        $warna = ['amber' => 'yellow'][$p->status_warna] ?? $p->status_warna;

        $steps = $p->events->map(fn ($e) => [
            'label' => $e->judul,
            'date'  => $e->waktu ? $e->waktu->copy()->locale('id')->translatedFormat('d M Y') : '',
            'done'  => (bool) $e->selesai,
        ])->all();

        $details = [
            [
                ['Pelanggan', $p->pelanggan?->nama ?? '-'],
                ['Muatan', $p->muatan],
            ],
            [
                ['Asal', $p->asal ?: '-'],
                ['Tujuan', $p->tujuan],
            ],
            [
                ['Armada', $p->truck ? 'Truk '.$p->truck->jenis.' '.$p->truck->plat : 'Belum ditentukan'],
                ['Tanggal Kirim', $p->tanggal_kirim?->locale('id')->translatedFormat('d M Y') ?? '-'],
            ],
        ];

        return [
            'resi'         => $p->no_resi,
            'status_label' => $p->status_label,
            'status_color' => $warna,
            'eta_text'     => $etaText,
            'eta_note'     => $etaNote,
            'steps'        => $steps,
            'details'      => $details,
            'map'          => PetaPengiriman::untuk($p),
        ];
    }

    /**
     * Mengembalikan [tanggal estimasi tiba, catatan "X Hari Lagi"].
     */
    private function buildEta(Pengiriman $p): array
    {
        if (! $p->estimasi_tiba) {
            return [null, null];
        }

        $eta = $p->estimasi_tiba->copy()->locale('id');

        if ($p->status === 'terkirim') {
            return [$eta->translatedFormat('d F Y'), 'Sudah Tiba'];
        }

        $days = (int) Carbon::today()->diffInDays($eta->copy()->startOfDay(), false);

        $note = $days > 0
            ? $days.' Hari Lagi'
            : ($days === 0 ? 'Hari Ini' : 'Terlambat '.abs($days).' Hari');

        return [$eta->translatedFormat('d F Y'), $note];
    }
}
