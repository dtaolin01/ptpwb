<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ekspor data BACA-SAJA untuk menyalin database website ke PostgreSQL (repo ptpwb-pg, skrip api/sinkron.php).
 * Hanya akun pemilik website (login lewat /api/admin/login). Tidak mengubah apa pun di database.
 *
 *   GET /api/admin/sinkron                                  daftar tabel + kunci + jumlah baris
 *   GET /api/admin/sinkron?tabel=supir&setelah=0&batas=500   isi tabel berurutan menurut kunci (halaman demi halaman)
 *   GET /api/admin/sinkron?tabel=supir&hanya_kunci=1        semua kunci baris (untuk mendeteksi baris yang dihapus)
 *
 * Kolom rahasia (password, PIN) dan tabel token/sesi tidak pernah dikirim.
 */
class SinkronController extends Controller
{
    /** tabel => kolom kunci (urutan halaman) */
    private const TABEL = [
        'admin_notifikasi'  => 'id',
        'aplikasi_versi'    => 'id',
        'barang'            => 'id',
        'bukti_foto'        => 'id',
        'checker'           => 'id',
        'contacts'          => 'id',
        'contact_messages'  => 'id',
        'lokasi_truk'       => 'id',
        'migrations'        => 'id',
        'notifikasi_pabrik' => 'id',
        'pelanggan'         => 'id',
        'pengaturan'        => 'kunci',
        'pengiriman'        => 'id',
        'services'          => 'id',
        'supir'             => 'id',
        'tracking_events'   => 'id',
        'trucks'            => 'id',
        'users'             => 'id',
    ];

    /** Kolom yang tidak boleh keluar dari server. */
    private const RAHASIA = [
        'users'   => ['password', 'remember_token'],
        'checker' => ['pin'],
    ];

    public function tarik(Request $request)
    {
        abort_unless($request->user()?->isPemilik(), 403, 'Hanya akun pemilik website yang boleh menyalin data.');

        $tabel = (string) $request->query('tabel', '');

        if ($tabel === '') {
            $daftar = [];
            foreach (self::TABEL as $nama => $kunci) {
                if (! Schema::hasTable($nama)) {
                    continue;
                }
                $daftar[$nama] = [
                    'kunci'  => $kunci,
                    'jumlah' => DB::table($nama)->count(),
                    'kolom'  => $this->kolom($nama),
                ];
            }

            return response()->json(['tabel' => $daftar, 'waktu_server' => now()->toIso8601String()]);
        }

        if (! isset(self::TABEL[$tabel]) || ! Schema::hasTable($tabel)) {
            return response()->json(['message' => "Tabel {$tabel} tidak tersedia untuk disalin."], 422);
        }

        $kunci = self::TABEL[$tabel];
        $angka = $kunci === 'id';
        $setelah = $request->query('setelah');

        if ($request->boolean('hanya_kunci')) {
            $q = DB::table($tabel)->orderBy($kunci);
            if ($setelah !== null && $setelah !== '') {
                $q->where($kunci, '>', $angka ? (int) $setelah : (string) $setelah);
            }
            $isi = $q->limit(5000)->pluck($kunci)->all();

            return response()->json([
                'tabel'      => $tabel,
                'kunci'      => $kunci,
                'daftar'     => $isi,
                'berikutnya' => count($isi) === 5000 ? end($isi) : null,
            ]);
        }

        $batas = max(1, min(1000, (int) $request->query('batas', 500)));
        $q = DB::table($tabel)->select($this->kolom($tabel))->orderBy($kunci)->limit($batas);
        if ($setelah !== null && $setelah !== '') {
            $q->where($kunci, '>', $angka ? (int) $setelah : (string) $setelah);
        }
        $baris = $q->get();

        return response()->json([
            'tabel'      => $tabel,
            'kunci'      => $kunci,
            'baris'      => $baris,
            'berikutnya' => $baris->count() === $batas ? $baris->last()->{$kunci} : null,
        ]);
    }

    /** Kolom tabel tanpa kolom rahasia. */
    private function kolom(string $tabel): array
    {
        return array_values(array_diff(Schema::getColumnListing($tabel), self::RAHASIA[$tabel] ?? []));
    }
}
