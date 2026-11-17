<?php

namespace App\Http\Controllers;

use App\Models\Checker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Halaman admin "Checker": akun petugas pabrik untuk aplikasi PWB Checker.
 * Login aplikasi memakai nomor telepon + PIN yang dibuat di sini.
 */
class CheckerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $data = Checker::query()
            ->when($q !== '', fn ($query) => $query->where('nama', 'like', "%{$q}%"))
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('checker', compact('data', 'q'));
    }

    public function create()
    {
        return view('checkerForm', ['checker' => new Checker(['aktif' => true])]);
    }

    public function store(Request $request)
    {
        Checker::create($this->validasi($request));

        return redirect()->route('checker')->with('success', 'Akun checker berhasil ditambahkan.');
    }

    public function edit(Checker $checker)
    {
        return view('checkerForm', compact('checker'));
    }

    public function update(Request $request, Checker $checker)
    {
        $data = $this->validasi($request, $checker);
        if (($data['pin'] ?? '') === '') {
            unset($data['pin']);   // PIN dikosongkan = tidak diubah
        }
        $checker->update($data);

        // Akun dinonaktifkan / PIN diganti → keluarkan dari aplikasi
        if (! $checker->aktif || isset($data['pin'])) {
            $checker->tokens()->delete();
        }

        return redirect()->route('checker')->with('success', 'Akun checker berhasil diperbarui.');
    }

    public function destroy(Checker $checker)
    {
        $checker->delete();

        return redirect()->route('checker')->with('success', 'Akun checker berhasil dihapus.');
    }

    private function validasi(Request $request, ?Checker $checker = null): array
    {
        $data = $request->validate([
            'nama'    => ['required', 'string', 'max:100'],
            'telepon' => ['required', 'regex:/^[0-9+\-\s]+$/', 'max:20', Rule::unique('checker', 'telepon')->ignore($checker?->id)],
            'pin'     => [$checker ? 'nullable' : 'required', 'digits_between:4,8'],
            'aktif'   => ['nullable', 'boolean'],
        ], [
            'telepon.regex'      => 'Nomor telepon hanya boleh berisi angka, spasi, + dan -.',
            'telepon.unique'     => 'Nomor telepon sudah dipakai checker lain.',
            'pin.digits_between' => 'PIN harus 4–8 angka.',
        ], [
            'pin' => 'PIN',
        ]);

        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }
}
