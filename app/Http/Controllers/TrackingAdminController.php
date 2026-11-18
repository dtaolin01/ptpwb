<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Pengiriman;
use App\Models\Truck;
use App\Support\Geocoder;
use App\Support\Kota;
use App\Support\LaporanPengiriman;
use App\Support\PetaPengiriman;
use App\Support\ProgresPengiriman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TrackingAdminController extends Controller
{
    /**
     * Daftar pengiriman + detail & peta pengiriman yang dipilih (atau dicari lewat resi).
     */
    public function index(Request $request)
    {
        $resi = strtoupper(trim((string) $request->query('resi')));
        $status = (string) $request->query('status');

        $daftar = Pengiriman::with('pelanggan')
            ->when($status !== '' && isset(Pengiriman::STATUS[$status]), fn ($q) => $q->where('status', $status))
            ->orderByDesc('tanggal_kirim')
            ->orderByDesc('id')
            ->paginate(8)
            ->withQueryString();

        $query = Pengiriman::with(['pelanggan', 'truck', 'events']);

        // Tanpa pencarian: tampilkan pengiriman terbaru
        $pengiriman = $resi !== ''
            ? $query->where('no_resi', $resi)->first()
            : $query->orderByDesc('tanggal_kirim')->orderByDesc('id')->first();

        if (! $pengiriman) {
            return view('trackingAdmin', [
                'resi'           => $resi,
                'status'         => $status,
                'daftar'         => $daftar,
                'pengiriman'     => null,
                'detail'         => null,
                'perjalanan'     => [],
                'updateTerakhir' => null,
                'peta'           => null,
            ]);
        }

        $detail = [
            'no_resi'   => $pengiriman->no_resi,
            'pelanggan' => $pengiriman->pelanggan?->nama ?? '-',
            'asal'      => $pengiriman->asal ?: '-',
            'tujuan'    => $pengiriman->tujuan,
            'muatan'    => $pengiriman->muatan,
            'rute'      => $pengiriman->rute_label,
            'armada'    => $pengiriman->truck
                ? 'Truk '.$pengiriman->truck->jenis.' '.$pengiriman->truck->plat
                : 'Belum ditentukan',
            'estimasi'  => $pengiriman->estimasi_tiba?->locale('id')->translatedFormat('j M Y') ?? '-',
            'status'    => $pengiriman->status_label,
            'warna'     => $pengiriman->status_warna,
        ];

        $perjalanan = $pengiriman->events->map(fn ($e) => [
            'judul'  => $e->judul,
            'waktu'  => $e->waktu ? $e->waktu->locale('id')->translatedFormat('j M Y, H:i').' WITA' : '',
            'lokasi' => $e->lokasi,
            'state'  => $e->selesai ? 'selesai' : 'menunggu',
        ]);

        $terakhir = $pengiriman->posisi_update
            ?? $pengiriman->events->where('selesai', true)->last()?->waktu;
        $updateTerakhir = $terakhir ? $terakhir->locale('id')->translatedFormat('j M Y, H:i').' WITA' : '-';

        $peta = PetaPengiriman::untuk($pengiriman);

        return view('trackingAdmin', compact(
            'resi', 'status', 'daftar', 'pengiriman', 'detail', 'perjalanan', 'updateTerakhir', 'peta'
        ));
    }

    public function create()
    {
        $pengiriman = new Pengiriman([
            'tanggal_kirim' => today(),
            'estimasi_tiba' => today()->addDay(),
            'status'        => 'menunggu',
            'lewat_pabrik'  => true,
        ]);

        // Langkah standar; tahap berikutnya dicentang otomatis dari lokasi HP supir
        $events = collect(Pengiriman::LANGKAH_STANDAR)->map(fn ($judul, $i) => [
            'judul'   => $judul,
            'waktu'   => $i === 0 ? now()->format('Y-m-d\TH:i') : '',
            'lokasi'  => '',
            'selesai' => $i === 0,
        ])->all();

        return $this->form($pengiriman, $events);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $pengiriman = DB::transaction(function () use ($data) {
            $data['no_resi'] = $data['no_resi'] ?: Pengiriman::resiBaru($data['tanggal_kirim'] ? \Carbon\Carbon::parse($data['tanggal_kirim']) : null);
            $p = Pengiriman::create($this->dataPengiriman($data));
            $this->simpanLangkah($p, $data['events'] ?? []);
            $this->rapikanLangkahRute($p);

            return $p;
        });

        return redirect()->route('trackingAdmin', ['resi' => $pengiriman->no_resi])
            ->with('success', "Pengiriman {$pengiriman->no_resi} berhasil ditambahkan.");
    }

    public function edit(Pengiriman $pengiriman)
    {
        $events = $pengiriman->events->map(fn ($e) => [
            'judul'   => $e->judul,
            'waktu'   => $e->waktu?->format('Y-m-d\TH:i') ?? '',
            'lokasi'  => $e->lokasi,
            'selesai' => $e->selesai,
        ])->all();

        return $this->form($pengiriman, $events);
    }

    public function update(Request $request, Pengiriman $pengiriman)
    {
        $data = $this->validasi($request, $pengiriman);

        DB::transaction(function () use ($data, $pengiriman) {
            $baru = $this->dataPengiriman($data);

            // Catat waktu kalau posisi truk berubah
            $titik = fn ($v) => $v === null || $v === '' ? null : round((float) $v, 6);
            $posisiBerubah = $titik($pengiriman->posisi_lat) !== $titik($baru['posisi_lat'])
                || $titik($pengiriman->posisi_lng) !== $titik($baru['posisi_lng']);
            if (! $posisiBerubah) {
                unset($baru['posisi_update']);
            }

            $pengiriman->update(array_merge($baru, ['no_resi' => $data['no_resi'] ?: $pengiriman->no_resi]));
            $this->simpanLangkah($pengiriman, $data['events'] ?? []);
            $this->rapikanLangkahRute($pengiriman);
        });

        return redirect()->route('trackingAdmin', ['resi' => $pengiriman->no_resi])
            ->with('success', "Data tracking {$pengiriman->no_resi} berhasil diperbarui.");
    }

    /**
     * Laporan satu pengiriman (siap cetak / simpan PDF dari browser).
     */
    public function laporan(Pengiriman $pengiriman)
    {
        return view('laporanPengiriman', [
            'p'   => $pengiriman,
            'lap' => LaporanPengiriman::untuk($pengiriman),
        ]);
    }

    public function destroy(Pengiriman $pengiriman)
    {
        $resi = $pengiriman->no_resi;
        $pengiriman->delete();

        return redirect()->route('trackingAdmin')->with('success', "Pengiriman {$resi} berhasil dihapus.");
    }

    private function form(Pengiriman $pengiriman, array $events)
    {
        return view('trackingForm', [
            'pengiriman' => $pengiriman,
            'events'     => old('events', $events),
            'pelanggan'  => Pelanggan::orderBy('nama')->get(['id', 'nama']),
            'trucks'     => Truck::with('supir:id,nama')->orderBy('plat')->get(['id', 'plat', 'jenis', 'supir_id']),
        ]);
    }

    private function validasi(Request $request, ?Pengiriman $pengiriman = null): array
    {
        $request->merge(['no_resi' => strtoupper(trim((string) $request->input('no_resi')))]);

        $koordinat = fn ($min, $max) => ['nullable', 'numeric', "between:$min,$max"];

        return $request->validate([
            'no_resi'          => ['nullable', 'string', 'max:50', Rule::unique('pengiriman', 'no_resi')->ignore($pengiriman?->id)],
            'pelanggan_id'     => ['required', 'exists:pelanggan,id'],
            'truck_id'         => ['nullable', 'exists:trucks,id'],
            'asal'             => ['nullable', 'string', 'max:150'],
            'tujuan'           => ['nullable', 'string', 'max:150'],
            'muatan'           => ['required', 'string', 'max:150'],
            'lewat_pabrik'     => ['required', 'boolean'],
            'tanggal_kirim'    => ['required', 'date'],
            'estimasi_tiba'    => ['nullable', 'date', 'after_or_equal:tanggal_kirim'],
            'status'           => ['required', Rule::in(array_keys(Pengiriman::STATUS))],
            'asal_lat'         => $koordinat(-90, 90),
            'asal_lng'         => $koordinat(-180, 180),
            'tujuan_lat'       => $koordinat(-90, 90),
            'tujuan_lng'       => $koordinat(-180, 180),
            'posisi_lat'       => $koordinat(-90, 90),
            'posisi_lng'       => $koordinat(-180, 180),
            'events'           => ['array', 'max:150'],
            'events.*.judul'   => ['required', 'string', 'max:150'],
            'events.*.waktu'   => ['nullable', 'date'],
            'events.*.lokasi'  => ['nullable', 'string', 'max:150'],
            'events.*.selesai' => ['nullable', 'boolean'],
        ], [
            'no_resi.unique'            => 'Nomor resi sudah dipakai.',
            'events.*.judul.required'   => 'Judul langkah tidak boleh kosong.',
            'estimasi_tiba.after_or_equal' => 'Estimasi tiba tidak boleh sebelum tanggal kirim.',
        ], [
            'pelanggan_id' => 'pelanggan',
            'truck_id'     => 'armada',
        ]);
    }

    /**
     * Ambil kolom tabel pengiriman. Yang dikosongkan admin diisi otomatis:
     * - Tujuan : titik & kota perusahaan pelanggan (dicari dari alamatnya)
     * - Asal   : posisi terakhir truk dari aplikasi supir (atau saat lokasi pertama masuk)
     */
    private function dataPengiriman(array $data): array
    {
        $kosong = fn ($titik) => ($data["{$titik}_lat"] ?? null) === null || ($data["{$titik}_lng"] ?? null) === null;

        // --- Tujuan dari lokasi perusahaan tujuan ---
        $pelanggan = Pelanggan::find($data['pelanggan_id']);
        if ($pelanggan && ! $pelanggan->titik() && $pelanggan->alamat) {
            PelangganController::isiLokasi($pelanggan);
        }
        if ($kosong('tujuan')) {
            [$data['tujuan_lat'], $data['tujuan_lng']] = $pelanggan?->titik() ?? Kota::cari($data['tujuan'] ?? null) ?? [null, null];
        }
        if (blank($data['tujuan'] ?? null)) {
            $data['tujuan'] = mb_substr($pelanggan?->kota ?: ($pelanggan?->alamat ?: '-'), 0, 150);
        }

        // --- Asal dari posisi truk saat ini ---
        if ($kosong('asal')) {
            if ($pos = ProgresPengiriman::posisiTerakhirTruk($data['truck_id'] ?? null)) {
                [$data['asal_lat'], $data['asal_lng']] = [$pos[0], $pos[1]];
                if (blank($data['asal'] ?? null)) {
                    $data['asal'] = Geocoder::namaDaerah($pos[0], $pos[1]) ?? 'Posisi truk';
                }
            } else {
                // Belum ada lokasi truk: diisi otomatis saat aplikasi supir mengirim lokasi pertama
                [$data['asal_lat'], $data['asal_lng']] = Kota::cari($data['asal'] ?? null) ?? [null, null];
            }
        }

        return [
            'pelanggan_id'  => $data['pelanggan_id'],
            'truck_id'      => $data['truck_id'] ?? null,
            'asal'          => $data['asal'] ?? null,
            'tujuan'        => $data['tujuan'],
            'muatan'        => $data['muatan'],
            'lewat_pabrik'  => (bool) $data['lewat_pabrik'],
            'tanggal_kirim' => $data['tanggal_kirim'],
            'estimasi_tiba' => $data['estimasi_tiba'] ?? null,
            'status'        => $data['status'],
            'asal_lat'      => $data['asal_lat'],
            'asal_lng'      => $data['asal_lng'],
            'tujuan_lat'    => $data['tujuan_lat'],
            'tujuan_lng'    => $data['tujuan_lng'],
            'posisi_lat'    => $data['posisi_lat'] ?? null,
            'posisi_lng'    => $data['posisi_lng'] ?? null,
            'posisi_update' => ($data['posisi_lat'] ?? null) !== null ? now() : null,
        ] + (isset($data['no_resi']) && $data['no_resi'] !== '' ? ['no_resi' => $data['no_resi']] : []);
    }

    /**
     * Rute dalam kota: langkah "Menuju Pabrik" & "Sedang Muat di Pabrik" yang belum dilewati dihapus.
     */
    private function rapikanLangkahRute(Pengiriman $p): void
    {
        if (! $p->lewat_pabrik) {
            $p->events()->whereIn('judul', Pengiriman::LANGKAH_PABRIK)->where('selesai', false)->delete();
            ProgresPengiriman::susunUlang($p);
        }
    }

    /**
     * Ganti semua langkah perjalanan dengan isian form (urutan sesuai form).
     */
    private function simpanLangkah(Pengiriman $p, array $events): void
    {
        $p->events()->delete();

        foreach (array_values($events) as $i => $e) {
            $p->events()->create([
                'urutan'  => $i + 1,
                'judul'   => $e['judul'],
                'waktu'   => $e['waktu'] ?? null,
                'lokasi'  => $e['lokasi'] ?? null,
                'selesai' => ! empty($e['selesai']),
            ]);
        }
    }
}
