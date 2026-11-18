<?php

namespace App\Support;

use App\Models\LokasiTruk;
use App\Models\Pengiriman;
use Illuminate\Support\Carbon;

/**
 * Menyusun isi laporan satu pengiriman: data, waktu tiap tahap, durasi,
 * jarak tempuh (dari lokasi HP supir), ketepatan waktu, dan jejak rute di peta.
 */
class LaporanPengiriman
{
    public static function untuk(Pengiriman $p): array
    {
        $p->loadMissing(['pelanggan', 'truck.supir', 'events']);

        $events = $p->events->sortBy('urutan')->values();
        $waktuLangkah = fn (string $judul) => $events->first(fn ($e) => $e->judul === $judul && $e->selesai)?->waktu;

        // Jejak lokasi truk selama pengiriman ini (dari aplikasi supir)
        $jejak = LokasiTruk::where('pengiriman_id', $p->id)->orderBy('waktu')->orderBy('id')->get(['lat', 'lng', 'waktu']);

        // ----- Waktu tiap tahap -----
        $dibuat = $waktuLangkah(ProgresPengiriman::L_DIBUAT) ?? $p->created_at;
        $berangkat = $waktuLangkah(ProgresPengiriman::L_MENUJU_PABRIK)
            ?? ($p->lewat_pabrik ? null : $waktuLangkah(ProgresPengiriman::L_PENGIRIMAN))
            ?? $jejak->first()?->waktu;
        $tibaPabrik = $p->lewat_pabrik ? ($p->waktu_tiba_pabrik ?? $waktuLangkah(ProgresPengiriman::L_MUAT)) : null;
        $keluarPabrik = $p->lewat_pabrik ? ($p->waktu_keluar_pabrik ?? $waktuLangkah(ProgresPengiriman::L_PENGIRIMAN)) : null;
        $tibaTujuan = $p->waktu_tiba_tujuan ?? $waktuLangkah(ProgresPengiriman::L_TIBA);
        $selesai = $waktuLangkah(ProgresPengiriman::L_SELESAI) ?? ($p->status === 'terkirim' ? $p->updated_at : null);

        $tahap = $p->lewat_pabrik
            ? [
                ['Menuju pabrik', $berangkat, $tibaPabrik],
                ['Muat di pabrik', $tibaPabrik, $keluarPabrik],
                ['Perjalanan ke tujuan', $keluarPabrik, $tibaTujuan],
                ['Bongkar & konfirmasi', $tibaTujuan, $selesai],
            ]
            : [
                ['Perjalanan ke tujuan', $berangkat, $tibaTujuan],
                ['Bongkar & konfirmasi', $tibaTujuan, $selesai],
            ];

        $tahap = array_map(fn ($t) => [
            'nama'   => $t[0],
            'mulai'  => self::jam($t[1]),
            'selesai' => self::jam($t[2]),
            'durasi' => self::durasi($t[1], $t[2]),
        ], $tahap);

        // ----- Jarak tempuh: jumlah jarak antar titik lokasi HP -----
        $jarak = 0.0;
        for ($i = 1; $i < $jejak->count(); $i++) {
            $jarak += ProgresPengiriman::jarak(
                [(float) $jejak[$i - 1]->lat, (float) $jejak[$i - 1]->lng],
                [(float) $jejak[$i]->lat, (float) $jejak[$i]->lng]
            );
        }

        // ----- Ketepatan waktu dibanding estimasi tiba -----
        $ketepatan = null;
        if ($p->estimasi_tiba && $tibaTujuan) {
            $selisih = (int) $p->estimasi_tiba->copy()->startOfDay()->diffInDays(Carbon::parse($tibaTujuan)->startOfDay(), false);
            $ketepatan = match (true) {
                $selisih < 0  => ['Lebih cepat '.abs($selisih).' hari', 'green'],
                $selisih === 0 => ['Tepat waktu', 'green'],
                default       => ['Terlambat '.$selisih.' hari', 'red'],
            };
        }

        $truck = $p->truck;

        return [
            'selesai_status' => $p->status === 'terkirim',
            'data' => [
                'No. Resi'        => $p->no_resi,
                'Status'          => $p->status_label,
                'Rute'            => $p->lewat_pabrik ? 'Lewat pabrik (muat di '.Pabrik::data()['nama'].')' : 'Dalam kota (langsung ke tujuan)',
                'Asal'            => $p->asal ?: '-',
                'Tujuan'          => $p->tujuan,
                'Jenis Muatan'    => $p->muatan,
                'Tanggal Kirim'   => $p->tanggal_kirim?->locale('id')->translatedFormat('d F Y') ?? '-',
                'Estimasi Tiba'   => $p->estimasi_tiba?->locale('id')->translatedFormat('d F Y') ?? '-',
            ],
            'pelanggan' => [
                'Perusahaan' => $p->pelanggan?->nama ?? '-',
                'Kontak'     => $p->pelanggan?->kontak ?? '-',
                'Email'      => $p->pelanggan?->email ?? '-',
                'Alamat'     => $p->pelanggan?->alamat ?? '-',
            ],
            'armada' => [
                'Nomor Polisi' => $truck?->plat ?? '-',
                'Jenis Truck'  => $truck ? $truck->jenis.' · '.$truck->kapasitas_ton.' ton' : '-',
                'Supir'        => $truck?->supir?->nama ?? '-',
                'Telepon'      => $truck?->supir?->telepon ?? '-',
            ],
            'ringkasan' => [
                'berangkat'   => self::jam($berangkat),
                'tiba'        => self::jam($tibaTujuan),
                'selesai'     => self::jam($selesai),
                'total'       => self::durasi($berangkat, $selesai ?? $tibaTujuan),
                'jarak'       => $jejak->count() > 1 ? number_format($jarak / 1000, 1, ',', '.').' km' : '-',
                'titik'       => $jejak->count(),
                'ketepatan'   => $ketepatan,
            ],
            'tahap'    => $tahap,
            'langkah'  => $events->map(fn ($e) => [
                'judul'   => $e->judul,
                'waktu'   => self::jam($e->waktu),
                'lokasi'  => $e->lokasi,
                'selesai' => (bool) $e->selesai,
            ])->all(),
            'peta' => PetaPengiriman::untuk($p) + [
                'jejak' => $jejak->map(fn ($l) => [(float) $l->lat, (float) $l->lng])->values()->all(),
            ],
        ];
    }

    private static function jam(mixed $w): ?string
    {
        return $w ? Carbon::parse($w)->locale('id')->translatedFormat('d M Y, H.i') : null;
    }

    /** "2 jam 15 menit", "1 hari 3 jam", atau null kalau salah satu waktu belum ada. */
    private static function durasi(mixed $mulai, mixed $akhir): ?string
    {
        if (! $mulai || ! $akhir) {
            return null;
        }

        $menit = (int) round(Carbon::parse($mulai)->diffInMinutes(Carbon::parse($akhir), false));
        if ($menit < 0) {
            return null;
        }

        $hari = intdiv($menit, 1440);
        $jam = intdiv($menit % 1440, 60);
        $sisa = $menit % 60;

        return trim(($hari ? $hari.' hari ' : '').($jam ? $jam.' jam ' : '').(($sisa || (! $hari && ! $jam)) && ! $hari ? $sisa.' menit' : ''));
    }
}
