<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LokasiTruk;
use App\Models\Pengiriman;
use App\Models\SupirToken;
use App\Models\Truck;
use App\Support\DeteksiPabrik;
use App\Support\PetaPengiriman;
use App\Support\Pelacakan;
use App\Support\ProgresPengiriman;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * API untuk aplikasi Android supir.
 *
 *   POST /api/supir/login              telepon + plat         -> token
 *   GET  /api/supir/pesanan            pesanan aktif truk ini
 *   POST /api/supir/lokasi             lat, lng (tiap 30 menit)
 *   POST /api/supir/pesanan/{id}/selesai
 *   POST /api/supir/logout
 *
 * Semua data (nama supir, plat, perusahaan tujuan, muatan) diambil dari
 * data yang diatur admin: menu Supir, Truck & Supir, dan Tracking.
 */
class SupirApiController extends Controller
{

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
            'pelacakan' => Pelacakan::untukAplikasi(),
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
            'pelacakan' => Pelacakan::untukAplikasi(),
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
        // Epoch dari HP selalu UTC → ubah ke zona waktu website (WITA) sebelum disimpan
        $waktu = isset($data['waktu'])
            ? Carbon::createFromTimestampMs($data['waktu'])->setTimezone(config('app.timezone'))
            : now();
        if ($waktu->isFuture() || $waktu->lt(now()->subDays(2))) {
            $waktu = now();
        }

        $interval = DB::transaction(function () use ($data, $truck, $p, $waktu) {
            // Notifikasi untuk aplikasi Checker: truk mendekati / tiba di / keluar dari pabrik
            // (dibandingkan dengan lokasi sebelumnya, jadi dipanggil sebelum lokasi baru disimpan)
            DeteksiPabrik::periksa($truck->loadMissing('supir'), (float) $data['lat'], (float) $data['lng'], $waktu, $p);

            LokasiTruk::create([
                'truck_id'      => $truck->id,
                'pengiriman_id' => $p?->id,
                'lat'           => $data['lat'],
                'lng'           => $data['lng'],
                'akurasi'       => $data['akurasi'] ?? null,
                'waktu'         => $waktu,
            ]);

            // Posisi truk di peta + status perjalanan maju otomatis
            // (menuju pabrik → muat → pengiriman → tiba)
            $interval = $p
                ? ProgresPengiriman::proses($p, (float) $data['lat'], (float) $data['lng'], $waktu)
                : ProgresPengiriman::INTERVAL_NORMAL;

            // Dekat pabrik (dengan atau tanpa pesanan) → pantau tiap 15 menit untuk checker
            return DeteksiPabrik::dekatPabrik((float) $data['lat'], (float) $data['lng'])
                ? min($interval, ProgresPengiriman::INTERVAL_DEKAT)
                : $interval;
        });

        $p?->refresh();

        return response()->json([
            'message'       => 'Lokasi tersimpan.',
            'pengiriman_id' => $p?->id,
            'status'        => $p?->status,
            'status_label'  => $p?->status_label,
            // Aplikasi mengirim lokasi berikutnya setelah sekian menit
            // (15 menit saat dekat pabrik/tujuan, selain itu 30 menit)
            'interval_menit' => $interval,
            // Pengaturan pelacakan terbaru (diatur admin di website, tanpa perlu update aplikasi)
            'pelacakan'      => Pelacakan::untukAplikasi(),
        ]);
    }

    public function selesai(Request $request, int $id)
    {
        $truck = $request->attributes->get('truck');

        $p = Pengiriman::where('id', $id)
            ->where('truck_id', $truck->id)
            ->whereIn('status', Pengiriman::STATUS_AKTIF)
            ->first();

        if (! $p) {
            return response()->json(['message' => 'Pesanan tidak ditemukan atau sudah selesai.'], 404);
        }

        DB::transaction(function () use ($p, $truck) {
            ProgresPengiriman::selesai($p);
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
            ->whereIn('status', Pengiriman::STATUS_AKTIF)
            // Yang sedang berjalan didahulukan, lalu yang baru dibuat
            ->orderByRaw("CASE status WHEN 'muat' THEN 0 WHEN 'dalam_perjalanan' THEN 0 WHEN 'menuju_pabrik' THEN 0 WHEN 'tiba' THEN 0 WHEN 'menunggu' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal_kirim')
            ->orderByDesc('id')
            ->first();
    }

    private function formatPesanan(Pengiriman $p): array
    {
        $pabrik = \App\Support\Pabrik::data()['nama'];
        $keterangan = [
            'menunggu'         => $p->lewat_pabrik
                ? 'Pesanan baru. Muat dulu di '.$pabrik.'. Status berubah otomatis saat truk mulai jalan.'
                : 'Pesanan dalam kota, langsung ke tujuan. Status berubah otomatis saat truk mulai jalan.',
            'menuju_pabrik'    => 'Truk sedang menuju '.$pabrik.' untuk muat barang.',
            'muat'             => 'Truk sedang muat di '.$pabrik.'.',
            'dalam_perjalanan' => 'Pesanan sedang dalam perjalanan menuju tujuan.',
            'tiba'             => 'Truk sudah tiba di tujuan. Tekan "Selesaikan Pesanan" setelah bongkar muat.',
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
            'sudah_tiba'        => $p->status === 'tiba',
            'lewat_pabrik'      => $p->lewat_pabrik,
            'rute'              => $p->lewat_pabrik ? 'Lewat pabrik' : 'Dalam kota',
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
