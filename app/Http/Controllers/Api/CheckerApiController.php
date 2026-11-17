<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checker;
use App\Models\CheckerToken;
use App\Models\NotifikasiPabrik;
use App\Models\Pengiriman;
use App\Support\DeteksiPabrik;
use App\Support\Pabrik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * API untuk aplikasi Android PWB Checker (petugas di pabrik).
 *
 *   POST /api/checker/login          telepon + pin -> token
 *   GET  /api/checker/truk           truk di area pabrik & yang sedang mendekati
 *   GET  /api/checker/notifikasi     ?setelah=<id> : notifikasi baru (untuk notif HP)
 *   POST /api/checker/logout
 */
class CheckerApiController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'telepon' => ['required', 'string', 'max:30'],
            'pin'     => ['required', 'string', 'max:20'],
        ], [], ['telepon' => 'nomor telepon', 'pin' => 'PIN']);

        $telepon = self::normalTelepon($data['telepon']);

        $checker = Checker::where('aktif', true)->get()
            ->first(fn (Checker $c) => self::normalTelepon($c->telepon) === $telepon);

        if (! $checker || $telepon === '' || ! Hash::check($data['pin'], $checker->pin)) {
            return response()->json(['message' => 'Nomor telepon atau PIN salah. Hubungi admin bila akun belum dibuat.'], 422);
        }

        $plain = Str::random(60);
        CheckerToken::create([
            'checker_id'       => $checker->id,
            'token'            => hash('sha256', $plain),
            'terakhir_dipakai' => now(),
        ]);

        return response()->json([
            'token'   => $plain,
            'checker' => ['id' => $checker->id, 'nama' => $checker->nama],
            // Notifikasi sebelum login tidak dimunculkan lagi di HP
            'notifikasi_terakhir_id' => (int) NotifikasiPabrik::max('id'),
        ]);
    }

    public function truk()
    {
        $atur = Pabrik::data();
        ['di_pabrik' => $diPabrik, 'mendekati' => $mendekati] = DeteksiPabrik::trukSekitar();

        // Pesanan aktif tiap truk (untuk info muatan & tujuan)
        $truckIds = $diPabrik->concat($mendekati)->pluck('truck_id')->unique()->all();
        $pesanan = Pengiriman::with('pelanggan')
            ->whereIn('truck_id', $truckIds)
            ->whereIn('status', Pengiriman::STATUS_AKTIF)
            ->orderByDesc('tanggal_kirim')
            ->orderByDesc('id')
            ->get()
            ->unique('truck_id')
            ->keyBy('truck_id');

        $format = fn ($l) => [
            'truck_id'      => $l->truck_id,
            'plat'          => $l->truck->plat,
            'jenis'         => $l->truck->jenis,
            'nama_supir'    => $l->truck->supir?->nama ?? '-',
            'telepon_supir' => $l->truck->supir?->telepon,
            'lat'           => (float) $l->lat,
            'lng'           => (float) $l->lng,
            'jarak_m'       => (int) round($l->jarak_m),
            'jarak_label'   => DeteksiPabrik::km($l->jarak_m),
            'diperbarui'    => $l->waktu->toIso8601String(),
            'diperbarui_label' => $l->waktu->locale('id')->diffForHumans(),
            'pesanan'       => ($p = $pesanan->get($l->truck_id)) ? [
                'no_resi'           => $p->no_resi,
                'status'            => $p->status,
                'status_label'      => $p->status_label,
                'muatan'            => $p->muatan,
                'perusahaan_tujuan' => $p->pelanggan?->nama,
                'tujuan'            => $p->tujuan,
            ] : null,
        ];

        return response()->json([
            'pabrik' => [
                'diatur'        => $atur['lat'] !== null,
                'nama'          => $atur['nama'],
                'lat'           => $atur['lat'],
                'lng'           => $atur['lng'],
                'radius_pabrik' => $atur['radius_pabrik'],
                'jarak_dekat_m' => (int) ($atur['jarak_dekat'] * 1000),
            ],
            'di_pabrik' => $diPabrik->map($format)->values(),
            'mendekati' => $mendekati->map($format)->values(),
            'diperbarui' => now()->toIso8601String(),
        ]);
    }

    /**
     * Tanpa ?setelah : 50 notifikasi terakhir (riwayat).
     * Dengan ?setelah=ID : notifikasi yang lebih baru dari ID itu (untuk notif HP).
     */
    public function notifikasi(Request $request)
    {
        $setelah = $request->integer('setelah', -1);

        $q = NotifikasiPabrik::query();
        $daftar = $setelah >= 0
            ? $q->where('id', '>', $setelah)->orderBy('id')->limit(50)->get()
            : $q->orderByDesc('id')->limit(50)->get();

        return response()->json([
            'data' => $daftar->map(fn (NotifikasiPabrik $n) => [
                'id'          => $n->id,
                'jenis'       => $n->jenis,
                'judul'       => $n->judul,
                'pesan'       => $n->pesan,
                'plat'        => $n->plat,
                'nama_supir'  => $n->nama_supir,
                'jarak_m'     => $n->jarak_m,
                'waktu'       => $n->waktu->toIso8601String(),
                'waktu_label' => $n->waktu->locale('id')->translatedFormat('d M, H.i'),
            ])->values(),
            'terakhir_id' => (int) NotifikasiPabrik::max('id'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('checker_token')->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    /** 0812-3456 / +62 812 3456 / 62812... -> 08123456 */
    private static function normalTelepon(string $telepon): string
    {
        $angka = preg_replace('/\D/', '', $telepon);

        if (str_starts_with($angka, '62')) {
            $angka = '0'.substr($angka, 2);
        } elseif (str_starts_with($angka, '8')) {
            $angka = '0'.$angka;
        }

        return $angka;
    }
}
