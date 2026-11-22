<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use App\Support\WhatsApp;
use Illuminate\Http\Request;

/** Halaman admin "WhatsApp Otomatis": token Fonnte, tombol aktif, dan kirim pesan uji coba. */
class WhatsAppController extends Controller
{
    public function edit()
    {
        $token = WhatsApp::token();

        return view('whatsapp', [
            'aktif'      => (bool) Pengaturan::ambil('wa_aktif', false),
            'adaToken'   => $token !== null,
            'tokenSamar' => $token ? substr($token, 0, 4).str_repeat('•', 8).substr($token, -3) : null,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => ['nullable', 'string', 'max:200'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        // Token dikosongkan = tetap pakai token lama
        if (filled($data['token'] ?? null)) {
            Pengaturan::simpan('wa_token', trim($data['token']));
        }
        Pengaturan::simpan('wa_aktif', $request->boolean('aktif') ? 1 : 0);

        if ($request->boolean('aktif') && ! WhatsApp::token()) {
            return back()->with('error', 'Isi token Fonnte dulu sebelum mengaktifkan.');
        }

        return back()->with('success', 'Pengaturan WhatsApp disimpan.');
    }

    public function tes(Request $request)
    {
        $data = $request->validate(['nomor' => ['required', 'string', 'max:30']], [], ['nomor' => 'nomor WhatsApp']);

        $nomor = WhatsApp::nomor($data['nomor']);
        if (! $nomor) {
            return back()->withInput()->with('error', 'Nomor WhatsApp tidak valid.');
        }

        [$ok, $ket] = WhatsApp::kirim($nomor, "Tes WhatsApp otomatis dari website PT Putra Wijaya Berjaya berhasil ✅");

        return back()->withInput()->with($ok ? 'success' : 'error', $ok ? 'Pesan uji coba terkirim ke '.$data['nomor'].'.' : 'Gagal: '.$ket);
    }
}
