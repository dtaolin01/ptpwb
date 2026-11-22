<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PetaArmadaController;
use App\Http\Controllers\TrackingAdminController;
use App\Models\AdminNotifikasi;
use App\Models\AdminToken;
use App\Models\Checker;
use App\Models\ContactMessage;
use App\Models\LokasiTruk;
use App\Models\Pelanggan;
use App\Models\Pengiriman;
use App\Models\Supir;
use App\Models\Truck;
use App\Models\User;
use App\Support\PetaPengiriman;
use App\Support\ProgresPengiriman;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * API untuk aplikasi Android PWB Admin. Semua jawaban JSON, login dengan akun admin website.
 *
 *   POST   /api/admin/login                     identifier + password → token
 *   GET    /api/admin/ringkasan                 angka, peringatan, pengiriman terbaru
 *   GET    /api/admin/armada                    semua truk + lokasi terakhir (Peta Armada)
 *   GET    /api/admin/pengiriman                ?q=&status=&page=
 *   GET    /api/admin/pengiriman/opsi           pilihan pelanggan / truk / status untuk form
 *   GET    /api/admin/pengiriman/{id}           detail + riwayat + peta + jejak
 *   POST   /api/admin/pengiriman                buat
 *   PUT    /api/admin/pengiriman/{id}           ubah
 *   POST   /api/admin/pengiriman/{id}/selesai   tandai selesai
 *   DELETE /api/admin/pengiriman/{id}
 *   GET|POST /api/admin/{truk|supir|pelanggan|checker}, PUT|DELETE .../{id}
 *   GET    /api/admin/notifikasi                ?setelah=<id>
 *   GET    /api/admin/pesan, GET /pesan/{id}, DELETE /pesan/{id}
 *   GET    /api/admin/akun, POST /akun/{id}/setujui, DELETE /akun/{id}   (pemilik saja)
 *   POST   /api/admin/logout
 */
class AdminApiController extends Controller
{
    /** Truk dengan pesanan aktif dianggap "GPS mati" kalau tidak kirim lokasi selama ini (menit). */
    private const MENIT_GPS_MATI = 45;

    /* ================================================================== */
    /*  Login                                                              */
    /* ================================================================== */

    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:150'],
            'password'   => ['required', 'string', 'max:150'],
            'perangkat'  => ['nullable', 'string', 'max:100'],
        ], [], ['identifier' => 'username / email', 'password' => 'password']);

        $field = filter_var($data['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::validate([$field => $data['identifier'], 'password' => $data['password']])) {
            return response()->json(['message' => 'Username/email atau password salah.'], 422);
        }

        $user = User::where($field, $data['identifier'])->first();
        if (! $user->disetujui) {
            return response()->json(['message' => 'Akun Anda belum disetujui pemilik website.'], 403);
        }

        $plain = Str::random(60);
        AdminToken::create([
            'user_id'          => $user->id,
            'token'            => hash('sha256', $plain),
            'perangkat'        => $data['perangkat'] ?? null,
            'terakhir_dipakai' => now(),
        ]);

        return response()->json([
            'token' => $plain,
            'admin' => $this->profil($user),
            // Notifikasi sebelum login tidak dimunculkan lagi di HP
            'notifikasi_terakhir_id' => (int) AdminNotifikasi::max('id'),
        ]);
    }

    public function saya(Request $request)
    {
        return response()->json(['admin' => $this->profil($request->user())]);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('admin_token')->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    private function profil(User $u): array
    {
        return ['id' => $u->id, 'nama' => $u->label, 'pemilik' => $u->isPemilik()];
    }

    /* ================================================================== */
    /*  Beranda                                                            */
    /* ================================================================== */

    public function ringkasan(Request $request)
    {
        $aktif = Pengiriman::whereIn('status', Pengiriman::STATUS_AKTIF);

        $perStatus = Pengiriman::whereIn('status', Pengiriman::STATUS_AKTIF)
            ->select('status', DB::raw('count(*) as jumlah'))->groupBy('status')->pluck('jumlah', 'status');

        $statusList = collect(Pengiriman::STATUS_AKTIF)->map(fn ($s) => [
            'status' => $s,
            'label'  => Pengiriman::STATUS[$s][0],
            'warna'  => Pengiriman::STATUS[$s][1],
            'jumlah' => (int) ($perStatus[$s] ?? 0),
        ])->values();

        $terbaru = Pengiriman::with(['pelanggan', 'truck'])
            ->orderByDesc('updated_at')->orderByDesc('id')->limit(6)->get()
            ->map(fn ($p) => $this->ringkasPengiriman($p));

        return response()->json([
            'admin' => $this->profil($request->user()),
            'angka' => [
                'pengiriman_aktif'  => (clone $aktif)->count(),
                'selesai_hari_ini'  => Pengiriman::where('status', 'terkirim')->whereDate('updated_at', today())->count(),
                'selesai_bulan_ini' => Pengiriman::where('status', 'terkirim')->where('updated_at', '>=', now()->startOfMonth())->count(),
                'truk_total'        => Truck::count(),
                'truk_jalan'        => Truck::where('status', 'digunakan')->count(),
                'truk_tersedia'     => Truck::where('status', 'tersedia')->count(),
                'pelanggan'         => Pelanggan::count(),
                'pesan_baru'        => ContactMessage::where('dibaca', false)->count(),
                'akun_menunggu'     => $request->user()->isPemilik() ? User::where('disetujui', false)->count() : 0,
            ],
            'per_status' => $statusList,
            'peringatan' => $this->peringatan(),
            'terbaru'    => $terbaru,
            'diperbarui' => now()->locale('id')->translatedFormat('H.i'),
        ]);
    }

    /**
     * Hal yang perlu ditindaklanjuti admin:
     *  - GPS mati: truk dengan pesanan aktif tidak kirim lokasi > 45 menit
     *  - Terlambat: pengiriman aktif melewati estimasi tiba
     */
    private function peringatan(): array
    {
        $hasil = [];

        $aktif = Pengiriman::with(['truck.supir', 'pelanggan'])
            ->whereIn('status', Pengiriman::STATUS_AKTIF)->whereNotNull('truck_id')->get();

        $lokasi = LokasiTruk::whereIn('truck_id', $aktif->pluck('truck_id'))
            ->select('truck_id', DB::raw('max(waktu) as terakhir'))->groupBy('truck_id')->pluck('terakhir', 'truck_id');

        foreach ($aktif as $p) {
            if (! $p->truck) {
                continue;
            }
            $terakhir = isset($lokasi[$p->truck_id]) ? Carbon::parse($lokasi[$p->truck_id]) : null;
            if ($p->status !== 'menunggu' && (! $terakhir || $terakhir->lt(now()->subMinutes(self::MENIT_GPS_MATI)))) {
                $hasil[] = [
                    'jenis'         => 'gps',
                    'judul'         => 'Lokasi '.$p->truck->plat.' tidak masuk',
                    'isi'           => $terakhir
                        ? 'Terakhir '.$terakhir->locale('id')->diffForHumans().' · '.($p->truck->supir?->nama ?? 'tanpa supir')
                        : 'Belum pernah mengirim lokasi · '.($p->truck->supir?->nama ?? 'tanpa supir'),
                    'telepon'       => $p->truck->supir?->telepon,
                    'pengiriman_id' => $p->id,
                ];
            }
            if ($p->estimasi_tiba && $p->estimasi_tiba->lt(today()) && $p->status !== 'tiba') {
                $hasil[] = [
                    'jenis'         => 'terlambat',
                    'judul'         => $p->no_resi.' melewati estimasi tiba',
                    'isi'           => 'Estimasi '.$p->estimasi_tiba->locale('id')->translatedFormat('j M').' · '.($p->pelanggan?->nama ?? '-'),
                    'telepon'       => $p->truck->supir?->telepon,
                    'pengiriman_id' => $p->id,
                ];
            }
        }

        return $hasil;
    }

    public function armada()
    {
        return response()->json(app(PetaArmadaController::class)->susun());
    }

    /* ================================================================== */
    /*  Pengiriman                                                         */
    /* ================================================================== */

    private function ringkasPengiriman(Pengiriman $p): array
    {
        return [
            'id'            => $p->id,
            'no_resi'       => $p->no_resi,
            'pelanggan'     => $p->pelanggan?->nama ?? '-',
            'asal'          => $p->asal ?: '-',
            'tujuan'        => $p->tujuan ?: '-',
            'muatan'        => $p->muatan,
            'plat'          => $p->truck?->plat,
            'status'        => $p->status,
            'status_label'  => $p->status_label,
            'warna'         => $p->status_warna,
            'tanggal_kirim' => $p->tanggal_kirim?->locale('id')->translatedFormat('j M Y'),
            'diperbarui'    => $p->updated_at?->locale('id')->diffForHumans(),
        ];
    }

    public function daftarPengiriman(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $status = (string) $request->query('status');

        $data = Pengiriman::with(['pelanggan', 'truck'])
            ->when($status === 'aktif', fn ($w) => $w->whereIn('status', Pengiriman::STATUS_AKTIF))
            ->when($status !== '' && isset(Pengiriman::STATUS[$status]), fn ($w) => $w->where('status', $status))
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('no_resi', 'like', "%{$q}%")
                        ->orWhere('tujuan', 'like', "%{$q}%")
                        ->orWhere('muatan', 'like', "%{$q}%")
                        ->orWhereHas('pelanggan', fn ($y) => $y->where('nama', 'like', "%{$q}%"))
                        ->orWhereHas('truck', fn ($y) => $y->where('plat', 'like', "%{$q}%"));
                });
            })
            ->orderByDesc('tanggal_kirim')->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'data'          => collect($data->items())->map(fn ($p) => $this->ringkasPengiriman($p)),
            'halaman'       => $data->currentPage(),
            'halaman_akhir' => $data->lastPage(),
            'total'         => $data->total(),
        ]);
    }

    public function opsiPengiriman()
    {
        return response()->json([
            'pelanggan' => Pelanggan::orderBy('nama')->get()->map(fn ($c) => [
                'id' => $c->id, 'nama' => $c->nama, 'kota' => $c->kota, 'ada_lokasi' => (bool) $c->titik(),
            ]),
            'truk' => Truck::with('supir')->orderBy('plat')->get()->map(fn ($t) => [
                'id' => $t->id, 'plat' => $t->plat, 'jenis' => $t->jenis, 'status' => $t->status,
                'status_label' => $t->status_label, 'supir' => $t->supir?->nama,
            ]),
            'status' => collect(Pengiriman::STATUS)->map(fn ($v, $k) => ['kode' => $k, 'label' => $v[0], 'warna' => $v[1]])->values(),
            'resi_baru' => Pengiriman::resiBaru(),
        ]);
    }

    public function detailPengiriman(int $id)
    {
        $p = Pengiriman::with(['pelanggan', 'truck.supir', 'events'])->findOrFail($id);

        $jejak = LokasiTruk::where('pengiriman_id', $p->id)->orderBy('waktu')->orderBy('id')
            ->get(['lat', 'lng'])->map(fn ($l) => [(float) $l->lat, (float) $l->lng]);
        // Batasi jumlah titik supaya ringan di HP
        if ($jejak->count() > 400) {
            $langkah = (int) ceil($jejak->count() / 400);
            $jejak = $jejak->filter(fn ($v, $i) => $i % $langkah === 0)->values();
        }

        $terakhir = $p->posisi_update ?? $p->events->where('selesai', true)->last()?->waktu;

        return response()->json([
            'pengiriman' => $this->ringkasPengiriman($p) + [
                'pelanggan_id'   => $p->pelanggan_id,
                'truck_id'       => $p->truck_id,
                'lewat_pabrik'   => (bool) $p->lewat_pabrik,
                'rute'           => $p->rute_label,
                'tanggal_kirim_iso' => $p->tanggal_kirim?->format('Y-m-d'),
                'estimasi_tiba'  => $p->estimasi_tiba?->locale('id')->translatedFormat('j M Y'),
                'estimasi_tiba_iso' => $p->estimasi_tiba?->format('Y-m-d'),
                'jenis_truk'     => $p->truck?->jenis,
                'supir'          => $p->truck?->supir?->nama,
                'telepon_supir'  => $p->truck?->supir?->telepon,
                'kontak_pelanggan'  => $p->pelanggan?->kontak,
                'telepon_pelanggan' => $p->pelanggan?->telepon,
                'email_pelanggan'   => $p->pelanggan?->email,
                'alamat_pelanggan'  => $p->pelanggan?->alamat,
                'update_terakhir'   => $terakhir?->locale('id')->translatedFormat('j M Y, H.i'),
                'update_relatif'    => $terakhir?->locale('id')->diffForHumans(),
                'laporan_dikirim'   => $p->laporan_dikirim_at
                    ? $p->laporan_dikirim_at->locale('id')->translatedFormat('j M Y, H.i').' ke '.$p->laporan_dikirim_ke
                    : null,
            ],
            'riwayat' => $p->events->map(fn ($e) => [
                'judul'   => $e->judul,
                'waktu'   => $e->waktu?->locale('id')->translatedFormat('j M Y, H.i'),
                'lokasi'  => $e->lokasi,
                'selesai' => (bool) $e->selesai,
            ])->values(),
            'peta'  => PetaPengiriman::untuk($p),
            'jejak' => $jejak->values(),
            'link'  => [
                'tracking_publik'   => route('tracking', ['resi' => $p->no_resi]),
                // Halaman laporan untuk pelanggan, dibuka di browser HP tanpa login (berlaku 2 jam)
                'laporan_pelanggan' => url(URL::temporarySignedRoute('laporanPelanggan.akses', now()->addHours(2), ['pengiriman' => $p->id], false)),
            ],
        ]);
    }

    private function validasiPengiriman(Request $request, ?Pengiriman $p = null): array
    {
        // Kolom titik tidak dikirim aplikasi → diisi otomatis (alamat pelanggan / posisi truk)
        $kosong = array_fill_keys(['asal', 'tujuan', 'asal_lat', 'asal_lng', 'tujuan_lat', 'tujuan_lng', 'posisi_lat', 'posisi_lng', 'truck_id', 'estimasi_tiba'], null);

        $data = $request->validate([
            'pelanggan_id'  => ['required', 'exists:pelanggan,id'],
            'truck_id'      => ['nullable', 'exists:trucks,id'],
            'muatan'        => ['required', 'string', 'max:150'],
            'lewat_pabrik'  => ['required', 'boolean'],
            'tanggal_kirim' => ['required', 'date'],
            'estimasi_tiba' => ['nullable', 'date', 'after_or_equal:tanggal_kirim'],
            'asal'          => ['nullable', 'string', 'max:150'],
            'tujuan'        => ['nullable', 'string', 'max:150'],
            'status'        => ['nullable', Rule::in(array_keys(Pengiriman::STATUS))],
            'no_resi'       => ['nullable', 'string', 'max:50', Rule::unique('pengiriman', 'no_resi')->ignore($p?->id)],
        ], [
            'estimasi_tiba.after_or_equal' => 'Estimasi tiba tidak boleh sebelum tanggal kirim.',
            'no_resi.unique'               => 'Nomor resi sudah dipakai.',
        ], ['pelanggan_id' => 'pelanggan', 'truck_id' => 'truk']);

        return $data + $kosong;   // isian yang dikirim menang; sisanya kosong
    }

    public function simpanPengiriman(Request $request)
    {
        $data = $this->validasiPengiriman($request);
        $web = app(TrackingAdminController::class);

        $p = DB::transaction(function () use ($data, $web) {
            $data['no_resi'] = ($data['no_resi'] ?? '') ?: Pengiriman::resiBaru(Carbon::parse($data['tanggal_kirim']));
            $data['status'] = $data['status'] ?? 'menunggu';
            $p = Pengiriman::create($web->dataPengiriman($data));

            // Langkah standar; tahap berikutnya dicentang otomatis dari lokasi HP supir
            $langkah = collect(Pengiriman::LANGKAH_STANDAR)->map(fn ($judul, $i) => [
                'judul' => $judul, 'waktu' => $i === 0 ? now() : null, 'lokasi' => null, 'selesai' => $i === 0,
            ])->all();
            $web->simpanLangkah($p, $langkah);
            $web->rapikanLangkahRute($p);

            if ($p->truck_id && in_array($p->status, Pengiriman::STATUS_AKTIF, true)) {
                Truck::whereKey($p->truck_id)->update(['status' => 'digunakan']);
            }

            return $p;
        });

        return response()->json(['message' => "Pengiriman {$p->no_resi} dibuat.", 'id' => $p->id], 201);
    }

    public function ubahPengiriman(Request $request, int $id)
    {
        $p = Pengiriman::findOrFail($id);
        $data = $this->validasiPengiriman($request, $p);
        $web = app(TrackingAdminController::class);

        DB::transaction(function () use ($data, $p, $web) {
            $data['status'] = $data['status'] ?? $p->status;
            // Titik asal/tujuan & posisi lama dipertahankan kalau tempatnya tidak diganti
            foreach (['asal', 'tujuan'] as $t) {
                if ($data[$t] === null || $data[$t] === '' || $data[$t] === $p->$t) {
                    $data[$t] = $p->$t;
                    $data["{$t}_lat"] = $p->{"{$t}_lat"};
                    $data["{$t}_lng"] = $p->{"{$t}_lng"};
                }
            }
            if ((int) $data['pelanggan_id'] !== (int) $p->pelanggan_id) {
                $data['tujuan'] = null;   // pelanggan diganti → tujuan dicari ulang dari alamatnya
                $data['tujuan_lat'] = $data['tujuan_lng'] = null;
            }
            $data['posisi_lat'] = $p->posisi_lat;
            $data['posisi_lng'] = $p->posisi_lng;

            $baru = $web->dataPengiriman($data);
            $baru['posisi_update'] = $p->posisi_update;
            $p->update(array_merge($baru, ['no_resi' => ($data['no_resi'] ?? '') ?: $p->no_resi]));
            $web->rapikanLangkahRute($p);
        });

        return response()->json(['message' => "Pengiriman {$p->no_resi} diperbarui."]);
    }

    public function selesaikanPengiriman(int $id)
    {
        $p = Pengiriman::with('truck')->findOrFail($id);
        if ($p->status === 'terkirim') {
            return response()->json(['message' => 'Pengiriman ini sudah selesai.'], 422);
        }

        DB::transaction(function () use ($p) {
            ProgresPengiriman::selesai($p);
            $p->truck?->update(['status' => 'tersedia']);
        });

        return response()->json(['message' => "Pengiriman {$p->no_resi} ditandai selesai."]);
    }

    public function hapusPengiriman(int $id)
    {
        $p = Pengiriman::findOrFail($id);
        $resi = $p->no_resi;
        $p->delete();

        return response()->json(['message' => "Pengiriman {$resi} dihapus."]);
    }

    /* ================================================================== */
    /*  Data master: Truk, Supir, Pelanggan, Checker                       */
    /* ================================================================== */

    public function daftarTruk(Request $request)
    {
        $q = trim((string) $request->query('q'));

        return response()->json(['data' => Truck::with('supir')
            ->when($q !== '', fn ($w) => $w->where('plat', 'like', "%{$q}%")
                ->orWhereHas('supir', fn ($y) => $y->where('nama', 'like', "%{$q}%")))
            ->orderBy('plat')->get()->map(fn ($t) => [
                'id' => $t->id, 'plat' => $t->plat, 'jenis' => $t->jenis, 'kapasitas_ton' => $t->kapasitas_ton,
                'status' => $t->status, 'status_label' => $t->status_label,
                'supir_id' => $t->supir_id, 'supir' => $t->supir?->nama, 'telepon_supir' => $t->supir?->telepon,
                'foto' => $t->foto ? asset('images/'.$t->foto) : null,
            ]),
            'jenis'  => Truck::JENIS,
            'status' => collect(Truck::STATUS)->map(fn ($v, $k) => ['kode' => $k, 'label' => $v[0]])->values(),
            'supir'  => Supir::orderBy('nama')->get(['id', 'nama'])->map(fn ($s) => [
                'id' => $s->id, 'nama' => $s->nama, 'truck_id' => Truck::where('supir_id', $s->id)->value('id'),
            ]),
        ]);
    }

    private function validasiTruk(Request $request, ?Truck $t = null): array
    {
        return $request->validate([
            'plat'          => ['required', 'string', 'max:20', Rule::unique('trucks', 'plat')->ignore($t?->id)],
            'jenis'         => ['required', Rule::in(Truck::JENIS)],
            'kapasitas_ton' => ['required', 'integer', 'min:1', 'max:100'],
            'supir_id'      => ['nullable', 'exists:supir,id', Rule::unique('trucks', 'supir_id')->ignore($t?->id)],
            'status'        => ['required', Rule::in(array_keys(Truck::STATUS))],
        ], [
            'plat.unique'     => 'Nomor polisi sudah terdaftar.',
            'supir_id.unique' => 'Supir ini sudah memegang truk lain.',
        ], ['plat' => 'nomor polisi', 'kapasitas_ton' => 'kapasitas', 'supir_id' => 'supir']);
    }

    public function simpanTruk(Request $request)
    {
        $data = $this->validasiTruk($request);
        $data['plat'] = strtoupper($data['plat']);
        Truck::create($data);

        return response()->json(['message' => 'Truk '.$data['plat'].' ditambahkan.'], 201);
    }

    public function ubahTruk(Request $request, int $id)
    {
        $t = Truck::findOrFail($id);
        $data = $this->validasiTruk($request, $t);
        $data['plat'] = strtoupper($data['plat']);
        $t->update($data);

        return response()->json(['message' => 'Truk '.$t->plat.' diperbarui.']);
    }

    public function hapusTruk(int $id)
    {
        $t = Truck::findOrFail($id);
        if (Pengiriman::where('truck_id', $t->id)->whereIn('status', Pengiriman::STATUS_AKTIF)->exists()) {
            return response()->json(['message' => 'Truk masih punya pengiriman aktif.'], 422);
        }
        $t->delete();

        return response()->json(['message' => 'Truk '.$t->plat.' dihapus.']);
    }

    public function daftarSupir(Request $request)
    {
        $q = trim((string) $request->query('q'));

        return response()->json(['data' => Supir::with('trucks')
            ->when($q !== '', fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('telepon', 'like', "%{$q}%"))
            ->orderBy('nama')->get()->map(fn ($s) => [
                'id' => $s->id, 'nama' => $s->nama, 'telepon' => $s->telepon,
                'truk' => $s->trucks->pluck('plat')->implode(', ') ?: null,
            ])]);
    }

    private function validasiSupir(Request $request): array
    {
        return $request->validate([
            'nama'    => ['required', 'string', 'max:100'],
            'telepon' => ['nullable', 'regex:/^[0-9+\-\s]+$/', 'max:20'],
        ], ['telepon.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, + dan -.']);
    }

    public function simpanSupir(Request $request)
    {
        $s = Supir::create($this->validasiSupir($request));

        return response()->json(['message' => 'Supir '.$s->nama.' ditambahkan.'], 201);
    }

    public function ubahSupir(Request $request, int $id)
    {
        $s = Supir::findOrFail($id);
        $s->update($this->validasiSupir($request));

        return response()->json(['message' => 'Supir '.$s->nama.' diperbarui.']);
    }

    public function hapusSupir(int $id)
    {
        $s = Supir::findOrFail($id);
        $s->delete();   // truk.supir_id otomatis kosong

        return response()->json(['message' => 'Supir '.$s->nama.' dihapus.']);
    }

    public function daftarPelanggan(Request $request)
    {
        $q = trim((string) $request->query('q'));

        return response()->json(['data' => Pelanggan::withCount('pengiriman')
            ->when($q !== '', fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('kota', 'like', "%{$q}%"))
            ->orderBy('nama')->get()->map(fn ($c) => [
                'id' => $c->id, 'nama' => $c->nama, 'kontak' => $c->kontak, 'telepon' => $c->telepon,
                'email' => $c->email, 'alamat' => $c->alamat, 'kota' => $c->kota,
                'ada_lokasi' => (bool) $c->titik(), 'jumlah_pengiriman' => $c->pengiriman_count,
            ])]);
    }

    private function validasiPelanggan(Request $request, ?Pelanggan $c = null): array
    {
        return $request->validate([
            'nama'    => ['required', 'string', 'max:150'],
            'kontak'  => ['required', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{8,30}$/'],
            'email'   => ['required', 'email', 'max:150', Rule::unique('pelanggan', 'email')->ignore($c?->id)],
            'alamat'  => ['required', 'string', 'max:255'],
        ], [
            'email.unique'  => 'Email sudah dipakai pelanggan lain.',
            'telepon.regex' => 'Nomor WhatsApp hanya boleh berisi angka, contoh 0812 3456 7890.',
        ], ['kontak' => 'nama kontak', 'telepon' => 'nomor WhatsApp']);
    }

    public function simpanPelanggan(Request $request)
    {
        $data = $this->validasiPelanggan($request);
        $web = app(PelangganController::class);
        $c = Pelanggan::create($web->tanpaTitik($data));
        $ketemu = $web->simpanTitik($c, $data, true);

        return response()->json([
            'message' => 'Pelanggan '.$c->nama.' ditambahkan.'.($ketemu ? '' : ' Lokasi alamat belum ditemukan otomatis; atur titiknya di website.'),
        ], 201);
    }

    public function ubahPelanggan(Request $request, int $id)
    {
        $c = Pelanggan::findOrFail($id);
        $data = $this->validasiPelanggan($request, $c);
        $alamatBerubah = trim((string) $c->alamat) !== trim($data['alamat']);
        $web = app(PelangganController::class);
        $c->update($web->tanpaTitik($data));
        $ketemu = $web->simpanTitik($c, $data, $alamatBerubah);

        return response()->json([
            'message' => 'Pelanggan '.$c->nama.' diperbarui.'.($ketemu ? '' : ' Lokasi alamat belum ditemukan otomatis; atur titiknya di website.'),
        ]);
    }

    public function hapusPelanggan(int $id)
    {
        $c = Pelanggan::findOrFail($id);
        if ($c->pengiriman()->exists()) {
            return response()->json(['message' => 'Pelanggan tidak bisa dihapus karena masih punya data pengiriman.'], 422);
        }
        $c->delete();

        return response()->json(['message' => 'Pelanggan '.$c->nama.' dihapus.']);
    }

    public function daftarChecker()
    {
        return response()->json(['data' => Checker::orderBy('nama')->get()->map(fn ($c) => [
            'id' => $c->id, 'nama' => $c->nama, 'telepon' => $c->telepon, 'aktif' => (bool) $c->aktif,
        ])]);
    }

    private function validasiChecker(Request $request, ?Checker $c = null): array
    {
        return $request->validate([
            'nama'    => ['required', 'string', 'max:100'],
            'telepon' => ['required', 'regex:/^[0-9+\-\s]+$/', 'max:20', Rule::unique('checker', 'telepon')->ignore($c?->id)],
            'pin'     => [$c ? 'nullable' : 'required', 'digits_between:4,8'],
            'aktif'   => ['nullable', 'boolean'],
        ], [
            'telepon.unique'     => 'Nomor telepon sudah dipakai checker lain.',
            'pin.digits_between' => 'PIN harus 4–8 angka.',
        ], ['pin' => 'PIN']);
    }

    public function simpanChecker(Request $request)
    {
        $data = $this->validasiChecker($request);
        $data['aktif'] = $data['aktif'] ?? true;
        $c = Checker::create($data);

        return response()->json(['message' => 'Checker '.$c->nama.' ditambahkan.'], 201);
    }

    public function ubahChecker(Request $request, int $id)
    {
        $c = Checker::findOrFail($id);
        $data = $this->validasiChecker($request, $c);
        if (($data['pin'] ?? '') === '') {
            unset($data['pin']);
        }
        $data['aktif'] = $data['aktif'] ?? $c->aktif;
        $c->update($data);
        if (! $c->aktif || isset($data['pin'])) {
            $c->tokens()->delete();   // keluarkan dari aplikasi Checker
        }

        return response()->json(['message' => 'Checker '.$c->nama.' diperbarui.']);
    }

    public function hapusChecker(int $id)
    {
        $c = Checker::findOrFail($id);
        $c->delete();

        return response()->json(['message' => 'Checker '.$c->nama.' dihapus.']);
    }

    /* ================================================================== */
    /*  Notifikasi, pesan masuk, persetujuan akun                          */
    /* ================================================================== */

    public function notifikasi(Request $request)
    {
        $this->catatGpsMati();

        $setelah = (int) $request->query('setelah', 0);
        $pemilik = $request->user()->isPemilik();

        $data = AdminNotifikasi::query()
            ->when(! $pemilik, fn ($q) => $q->where('jenis', '!=', 'akun'))
            ->when($setelah > 0, fn ($q) => $q->where('id', '>', $setelah)->orderBy('id'),
                fn ($q) => $q->orderByDesc('id'))
            ->limit(60)->get();

        return response()->json(['data' => $data->map(fn ($n) => $n->keArray())->values()]);
    }

    /** Buat notifikasi "GPS mati" (paling sering 1x per 3 jam per truk). Dipanggil saat HP admin mengecek notifikasi. */
    private function catatGpsMati(): void
    {
        foreach ($this->peringatan() as $w) {
            if ($w['jenis'] !== 'gps') {
                continue;
            }
            $p = Pengiriman::find($w['pengiriman_id']);
            $sudah = AdminNotifikasi::where('jenis', 'gps')->where('truck_id', $p?->truck_id)
                ->where('created_at', '>=', now()->subHours(3))->exists();
            if ($p && ! $sudah) {
                AdminNotifikasi::create([
                    'jenis' => 'gps', 'judul' => $w['judul'], 'isi' => $w['isi'],
                    'pengiriman_id' => $p->id, 'truck_id' => $p->truck_id,
                ]);
            }
        }
    }

    public function daftarPesan()
    {
        return response()->json(['data' => ContactMessage::orderByDesc('id')->limit(100)->get()->map(fn ($m) => [
            'id' => $m->id, 'nama' => $m->nama, 'email' => $m->email, 'telepon' => $m->telepon,
            'subjek' => $m->subjek, 'pesan' => $m->pesan, 'dibaca' => (bool) $m->dibaca,
            'waktu' => $m->created_at?->locale('id')->translatedFormat('j M Y, H.i'),
        ])]);
    }

    public function bacaPesan(int $id)
    {
        $m = ContactMessage::findOrFail($id);
        $m->update(['dibaca' => true]);

        return response()->json(['message' => 'ok']);
    }

    public function hapusPesan(int $id)
    {
        ContactMessage::findOrFail($id)->delete();

        return response()->json(['message' => 'Pesan dihapus.']);
    }

    public function daftarAkun(Request $request)
    {
        abort_unless($request->user()->isPemilik(), 403, 'Hanya pemilik website.');

        $map = fn (User $u) => [
            'id' => $u->id, 'nama' => $u->label, 'disetujui' => (bool) $u->disetujui, 'pemilik' => $u->isPemilik(),
            'waktu' => $u->created_at?->locale('id')->translatedFormat('j M Y, H.i'),
        ];

        return response()->json([
            'menunggu' => User::where('disetujui', false)->orderByDesc('created_at')->get()->map($map),
            'aktif'    => User::where('disetujui', true)->orderBy('username')->get()->map($map),
        ]);
    }

    public function setujuiAkun(Request $request, int $id)
    {
        abort_unless($request->user()->isPemilik(), 403, 'Hanya pemilik website.');
        $u = User::findOrFail($id);
        $u->update(['disetujui' => true, 'disetujui_at' => now()]);

        return response()->json(['message' => "Akun {$u->label} disetujui."]);
    }

    public function hapusAkun(Request $request, int $id)
    {
        abort_unless($request->user()->isPemilik(), 403, 'Hanya pemilik website.');
        $u = User::findOrFail($id);
        abort_if($u->isPemilik() || $u->is($request->user()), 422, 'Akun pemilik tidak bisa dihapus.');
        $u->delete();

        return response()->json(['message' => "Akun {$u->label} dihapus."]);
    }
}
