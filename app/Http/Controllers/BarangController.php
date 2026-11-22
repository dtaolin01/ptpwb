<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

/** Halaman admin "Data Barang": satu halaman untuk daftar, tambah, ubah, hapus. */
class BarangController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        return view('barang', [
            'q'     => $q,
            'data'  => Barang::when($q, fn ($x) => $x->where(fn ($w) => $w->where('nama', 'like', "%$q%")->orWhere('kode', 'like', "%$q%")))
                ->orderByDesc('dipakai')->orderBy('nama')->paginate(25)->withQueryString(),
            'ubah'  => $request->filled('ubah') ? Barang::find($request->query('ubah')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $b = Barang::create($this->validasi($request));

        return redirect()->route('barang')->with('success', 'Barang '.$b->nama.' disimpan.');
    }

    public function update(Request $request, Barang $barang)
    {
        $barang->update($this->validasi($request, $barang));

        return redirect()->route('barang')->with('success', 'Barang '.$barang->nama.' diperbarui.');
    }

    public function destroy(Barang $barang)
    {
        $barang->delete();

        return back()->with('success', 'Barang '.$barang->nama.' dihapus dari daftar.');
    }

    private function validasi(Request $request, ?Barang $b = null): array
    {
        return $request->validate([
            'nama'   => ['required', 'string', 'max:150', function ($attr, $nilai, $gagal) use ($b) {
                // Tidak boleh dobel walau beda huruf besar/kecil
                if (Barang::whereRaw('LOWER(nama) = ?', [mb_strtolower(trim($nilai))])->when($b, fn ($q) => $q->whereKeyNot($b->id))->exists()) {
                    $gagal('Barang dengan nama ini sudah ada.');
                }
            }],
            'kode'   => ['nullable', 'string', 'max:50'],
            'satuan' => ['nullable', 'string', 'max:20'],
        ], [], ['nama' => 'nama barang']);
    }
}
