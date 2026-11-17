<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LokasiTruk;
use App\Models\Pengiriman;
use App\Models\SupirToken;
use App\Models\Truck;
use App\Support\PetaPengiriman;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * API untuk aplikasi Android supir.
 *
 *   POST /api/supir/login              telepon + plat         -> token
 *   GET  /api/supir/pesanan            pesanan aktif truk ini
 *   POST /api/supir/lokasi             lat, lng (tiap 2 jam)
 *   POST /api/supir/pesanan/{id}/selesai
 *   POST /api/supir/logout
 *
 * Semua data (nama supir, plat, perusahaan tujuan, muatan) diambil dari
 * data yang diatur admin: menu Supir, Truck & Supir, dan Tracking.
 */
class SupirApiController extends Controller
{
    /** Status yang dianggap "pesanan saat ini" — dalam perjalanan didahulukan. */
    private const STATUS_AKTIF = ['dalam_perjalanan', 'menunggu', 'tertunda'];

    public function login(Request $request)
    {
        $data = $request->validate([
            'telepon' => ['required', 'string', 'max:30'],
            'plat'    => ['required', 'string', 'max:20'],
        ], [], ['telepon' => 'nomor telepon', 'plat' => 'plat nomor']);

        $telepon = self::normalTelepon($data['telepon']);
        $plat = self::normalPlat($data['plat']);

        $truck = Truck::with('supir')->get()
            ->first(fn (Truck $t) => self::normalPlat($t->plat) === $plat);

        if (! $truck || ! $truck->supir || self::normalTelepon((string) $truck->supir->telepon) !== $telepon || $telepon === '') {
            return response()->json([
                'message' => 'Nomor telepon atau plat nomor tidak cocok. Hubungi admin bila data Anda belum terdaftar.',
            ], 422);
        }

        $plain = Str::random(60);

        SupirToken::create([
            'supir_id'         => $truck->supir_id,
            'truck_id'         => $truck->id,
            'token'            => hash('sha256', $plain),
            'terakhir_dipakai' => now(),
        ]);

        return response()->json([
            'token' => $plain,
            'supir' => ['id' => $truck->supir->id, 'nama' => $truck->supir->nama],
            'truck' => ['id' => $truck->id, 'plat' => $truck->plat, 'jenis' => $truck->jenis],
        ]);
    }

    public function pesanan(Request $request)
    {
        $truck = $request->attributes->get('truck');
        $p = $this->pesananAktif($truck);

        return response()->json([
            'supir'   => ['nama' => $request->attributes->get('supir')->nama],
            'truck'   => ['plat' => $truck->plat, 'jenis' => $truck->jenis],
            'pesanan' => $p ? $this->formatPesanan($p) : null,
        ]);
    }

    public function lokasi(Request $request)
    {
        $data = $request->validate([
            'lat'     => ['required', 'numeric', 'between:-90,90'],
            'lng'     => ['required', 'numeric', 'between:-180,180'],
            'akurasi' => ['nullable', 'numeric', 'min:0'],
            'waktu'   => ['nullable', 'integer'],          // epoch millis dari HP
        ]);

        $truck = $request->attributes->get('truck');
        $p = $this->pesananAktif($truck);

        // Waktu dari HP dipakai kalau masuk akal (tidak di masa depan / terlalu lama)
        $waktu = isset($data['waktu']) ? Carbon::createFromTimestampMs($data['waktu']) : now();
        if ($waktu->isFuture() || $waktu->lt(now()->subDays(2))) {
            $waktu = now();
        }

        DB::transaction(function () use ($data, $truck, $p, $waktu) {
            LokasiTruk::create([
                'truck_id'      => $truck->id,
                'pengiriman_id' => $p?->id,
                'lat'           => $data['lat'],
                'lng'           => $data['lng'],
                'akurasi'       => $data['akurasi'] ?? null,
                'waktu'         => $waktu,
            ]);

            // Posisi truk di peta Tracking (website & admin) ikut berubah
            $p?->update([
                'posisi_lat'    => $data['lat'],
                'posisi_lng'    => $data['lng'],
                'posisi_update' => $waktu,
            ]);
        });

        return response()->json([
            'message'       => 'Lokasi tersimpan.',
            'pengiriman_id' => $p?->id,
        ]);
    }

    public function selesai(Request $request, int $id)
    {
        $truck = $request->attributes->get('truck');

        $p = Pengiriman::where('id', $id)
            ->where('truck_id', $truck->id)
            ->whereIn('status', self::STATUS_AKTIF)
            ->first();

        if (! $p) {
            return response()->json(['message' => 'Pesanan tidak ditemukan atau sudah selesai.'], 404);
        }

        DB::transaction(function () use ($p, $truck) {
            $p->update(['status' => 'terkirim']);

            // Langkah perjalanan yang belum selesai ditandai selesai
            $p->events()->where('selesai', false)->each(function ($e) {
                $e->update(['selesai' => true, 'waktu' => $e->waktu ?? now()]);
            });

            $truck->update(['status' => 'tersedia']);
        });

        return response()->json(['message' => 'Pesanan '.$p->no_resi.' selesai.']);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('supir_token')->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    /* ------------------------------------------------------------------ */

    private function pesananAktif(Truck $truck): ?Pengiriman
    {
        return Pengiriman::with(['pelanggan', 'truck.supir'])
            ->where('truck_id', $truck->id)
            ->whereIn('status', self::STATUS_AKTIF)
            ->orderByRaw("CASE status WHEN 'dalam_perjalanan' THEN 0 WHEN 'menunggu' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal_kirim')
            ->orderByDesc('id')
            ->first();
    }

    private function formatPesanan(Pengiriman $p): array
    {
        $keterangan = [
            'dalam_perjalanan' => 'Pesanan sedang dalam perjalanan menuju tujuan.',
            'menunggu'         => 'Pesanan menunggu keberangkatan.',
            'tertunda'         => 'Pengiriman tertunda. Hubungi admin bila perlu.',
        ];

        return [
            'id'                => $p->id,
            'no_resi'           => $p->no_resi,
            'asal'              => $p->asal,
            'tujuan'            => $p->tujuan,
            'status'            => $p->status,
            'status_label'      => $p->status_label,
            'status_keterangan' => $keterangan[$p->status] ?? '',
            'nama_supir'        => $p->truck?->supir?->nama,
            'plat'              => $p->truck?->plat,
            'perusahaan_tujuan' => $p->pelanggan?->nama,
            'muatan'            => $p->muatan,
            'tanggal_kirim'     => $p->tanggal_kirim?->format('Y-m-d'),
            'estimasi_tiba'     => $p->estimasi_tiba?->format('Y-m-d'),
            'peta'              => PetaPengiriman::untuk($p),
        ];
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

    /** "b 9876 kz" -> "B9876KZ" */
    private static function normalPlat(string $plat): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plat));
    }
}
