<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Support\Geocoder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PelangganController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $data = Pelanggan::query()
            ->when($q !== '', fn ($query) => $query->where('nama', 'like', "%{$q}%"))
            ->orderBy('id')
            ->paginate(5)
            ->withQueryString();

        $pelanggan = $data->getCollection()->map(fn ($p) => [
            'nama'   => $p->nama,
            'kontak' => $p->kontak,
            'telepon' => $p->telepon,
            'email'  => $p->email,
            'alamat' => $p->alamat,
            'lokasi' => $p->titik() !== null,
            'edit'   => route('pelanggan.edit', $p),
            'hapus'  => route('pelanggan.destroy', $p),
        ]);

        return view('pelanggan', compact('pelanggan', 'data', 'q'));
    }

    public function create()
    {
        return view('pelangganForm', ['pelanggan' => new Pelanggan()]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $pelanggan = Pelanggan::create($this->tanpaTitik($data));
        $ketemu = $this->simpanTitik($pelanggan, $data, true);

        return $this->kembali($pelanggan, $ketemu, 'Data pelanggan berhasil ditambahkan.');
    }

    public function edit(Pelanggan $pelanggan)
    {
        return view('pelangganForm', compact('pelanggan'));
    }

    public function update(Request $request, Pelanggan $pelanggan)
    {
        $data = $this->validasi($request, $pelanggan);
        $alamatBerubah = trim((string) $pelanggan->alamat) !== trim($data['alamat']);
        $pelanggan->update($this->tanpaTitik($data));
        $ketemu = $this->simpanTitik($pelanggan, $data, $alamatBerubah || ! empty($data['cari_ulang']));

        return $this->kembali($pelanggan, $ketemu, 'Data pelanggan berhasil diperbarui.');
    }

    /**
     * Cari titik lokasi perusahaan dari alamatnya lalu simpan.
     * Dipakai juga oleh form Tracking & perintah `php artisan pelanggan:lokasi`.
     */
    public static function isiLokasi(Pelanggan $pelanggan): bool
    {
        $hasil = Geocoder::cari($pelanggan->alamat);
        if (! $hasil) {
            return false;
        }

        $pelanggan->update([
            'lat'           => $hasil['lat'],
            'lng'           => $hasil['lng'],
            'kota'          => $hasil['kota'] ?? $pelanggan->kota,
            'lokasi_sumber' => 'otomatis',
        ]);

        return true;
    }

    /**
     * Titik dari peta (kalau admin menggeser/klik manual), atau dicari otomatis dari alamat.
     * Mengembalikan false kalau alamat tidak ditemukan.
     */
    private function simpanTitik(Pelanggan $pelanggan, array $data, bool $cariUlang): bool
    {
        $manual = ($data['lokasi_sumber'] ?? null) === 'manual'
            && ($data['lat'] ?? null) !== null && ($data['lng'] ?? null) !== null;

        if ($manual) {
            $pelanggan->update(['lat' => $data['lat'], 'lng' => $data['lng'], 'lokasi_sumber' => 'manual']);

            return true;
        }

        if ($cariUlang || ! $pelanggan->titik()) {
            return self::isiLokasi($pelanggan);
        }

        return true;
    }

    private function tanpaTitik(array $data): array
    {
        return array_intersect_key($data, array_flip(['nama', 'kontak', 'telepon', 'email', 'alamat']));
    }

    private function kembali(Pelanggan $pelanggan, bool $ketemu, string $pesan)
    {
        if (! $ketemu) {
            return redirect()->route('pelanggan.edit', $pelanggan)
                ->with('error', 'Data tersimpan, tetapi lokasi alamat tidak ditemukan otomatis. Klik lokasi perusahaan di peta lalu simpan lagi.');
        }

        return redirect()->route('pelanggan')->with('success', $pesan);
    }

    public function destroy(Pelanggan $pelanggan)
    {
        // Jangan hapus pelanggan yang masih punya pengiriman (di migration pengiriman ikut terhapus / cascade)
        if ($pelanggan->pengiriman()->exists()) {
            return redirect()->route('pelanggan')
                ->with('error', 'Pelanggan tidak bisa dihapus karena masih punya data pengiriman.');
        }

        $pelanggan->delete();

        return redirect()->route('pelanggan')->with('success', 'Data pelanggan berhasil dihapus.');
    }

    private function validasi(Request $request, ?Pelanggan $pelanggan = null): array
    {
        return $request->validate([
            'nama'   => ['required', 'string', 'max:150'],
            'kontak' => ['required', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{8,30}$/'],
            'email'  => ['required', 'email', 'max:150', Rule::unique('pelanggan', 'email')->ignore($pelanggan?->id)],
            'alamat' => ['required', 'string', 'max:255'],
            'lat'    => ['nullable', 'numeric', 'between:-90,90'],
            'lng'    => ['nullable', 'numeric', 'between:-180,180'],
            'lokasi_sumber' => ['nullable', 'in:otomatis,manual'],
            'cari_ulang'    => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'Email sudah dipakai pelanggan lain.',
            'telepon.regex' => 'Nomor WhatsApp hanya boleh berisi angka, contoh 0812 3456 7890.',
        ], [
            'nama' => 'nama perusahaan',
        ]);
    }
}
