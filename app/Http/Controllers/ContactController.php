<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Halaman publik /contact: kartu info & lokasi diambil dari tabel contacts.
     */
    public function contact()
    {
        $contacts = Contact::where('category', 'info')->orderBy('sort_order')->orderBy('id')->get();
        $location = Contact::where('category', 'location')->orderBy('sort_order')->first();

        return view('contact', compact('contacts', 'location'));
    }

    /**
     * Simpan pesan dari form "Kirim Pesan" ke tabel contact_messages.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'    => ['required', 'string', 'max:150'],
            'email'   => ['required', 'email', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'subjek'  => ['required', 'string', 'max:150'],
            'pesan'   => ['required', 'string', 'max:2000'],
        ], [], [
            'nama'    => 'nama lengkap',
            'telepon' => 'no telepon',
        ]);

        ContactMessage::create($data);

        return redirect()->to(route('contact').'#kirim-pesan')
            ->with('success', 'Terima kasih, pesan Anda sudah terkirim. Tim kami akan segera menghubungi Anda.');
    }
}
