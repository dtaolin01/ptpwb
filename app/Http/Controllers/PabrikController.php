<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use App\Support\Geocoder;
use App\Support\Pabrik;
use Illuminate\Http\Request;

/**
 * Halaman admin "Lokasi Pabrik": satu pabrik tempat truk memuat barang,
 * plus radius untuk mendeteksi truk masuk/keluar pabrik & tiba di tujuan.
 */
class PabrikController extends Controller
{
    public function edit()
    {
        return view('pabrik', ['pabrik' => Pabrik::data()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nama'          => ['required', 'string', 'max:150'],
            'alamat'        => ['nullable', 'string', 'max:255'],
            'lat'           => ['nullable', 'numeric', 'between:-90,90'],
            'lng'           => ['nullable', 'numeric', 'between:-180,180'],
            'radius_pabrik' => ['required', 'integer', 'between:50,5000'],
            'radius_tujuan' => ['required', 'integer', 'between:50,5000'],
            'jarak_dekat'   => ['required', 'numeric', 'between:1,50'],
        ], [], [
            'nama'          => 'nama pabrik',
            'radius_pabrik' => 'radius area pabrik',
            'radius_tujuan' => 'radius area tujuan',
            'jarak_dekat'   => 'jarak mendekati pabrik',
        ]);

        // Titik belum dipilih di peta → cari dari alamat
        if (($data['lat'] ?? null) === null || ($data['lng'] ?? null) === null) {
            $hasil = Geocoder::cari($data['alamat'] ?? null);
            if (! $hasil) {
                return back()->withInput()
                    ->with('error', 'Lokasi pabrik belum ditemukan. Klik lokasi pabrik di peta, lalu simpan.');
            }
            $data['lat'] = $hasil['lat'];
            $data['lng'] = $hasil['lng'];
        }

        $kunci = [
            'nama' => 'pabrik_nama', 'alamat' => 'pabrik_alamat', 'lat' => 'pabrik_lat', 'lng' => 'pabrik_lng',
            'radius_pabrik' => 'radius_pabrik', 'radius_tujuan' => 'radius_tujuan', 'jarak_dekat' => 'jarak_dekat',
        ];
        foreach ($kunci as $field => $k) {
            Pengaturan::simpan($k, $data[$field] ?? null);
        }

        return redirect()->route('pabrik')->with('success', 'Lokasi pabrik berhasil disimpan.');
    }
}
