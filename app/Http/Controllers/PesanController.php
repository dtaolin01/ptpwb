<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

/**
 * Halaman admin "Pesan Masuk": daftar pesan dari form Contact.
 */
class PesanController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $data = ContactMessage::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('nama', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('subjek', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $belumDibaca = ContactMessage::where('dibaca', false)->count();

        return view('pesan', compact('data', 'q', 'belumDibaca'));
    }

    public function show(ContactMessage $pesan)
    {
        if (! $pesan->dibaca) {
            $pesan->update(['dibaca' => true]);
        }

        return view('pesanDetail', compact('pesan'));
    }

    public function destroy(ContactMessage $pesan)
    {
        $pesan->delete();

        return redirect()->route('pesan')->with('success', 'Pesan berhasil dihapus.');
    }
}
