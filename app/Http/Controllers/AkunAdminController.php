<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Menu "Persetujuan Akun" — hanya untuk pemilik website.
 * Akun yang mendaftar lewat /admin/register baru bisa login setelah disetujui di sini.
 */
class AkunAdminController extends Controller
{
    public function index()
    {
        return view('akunAdmin', [
            'menunggu'  => User::where('disetujui', false)->orderByDesc('created_at')->get(),
            'disetujui' => User::where('disetujui', true)->orderBy('username')->orderBy('email')->get(),
        ]);
    }

    public function setujui(User $user)
    {
        $user->update(['disetujui' => true, 'disetujui_at' => now()]);

        return back()->with('success', "Akun {$user->label} disetujui dan sekarang bisa login.");
    }

    /** Cabut akses: akun tetap ada tapi tidak bisa login lagi (bisa disetujui ulang). */
    public function cabut(Request $request, User $user)
    {
        abort_if($user->is($request->user()) || $user->isPemilik(), 422, 'Akun pemilik tidak bisa dicabut.');

        $user->update(['disetujui' => false, 'disetujui_at' => null]);

        return back()->with('success', "Akses akun {$user->label} dicabut. Akun langsung keluar dari dashboard.");
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->is($request->user()) || $user->isPemilik(), 422, 'Akun pemilik tidak bisa dihapus.');

        $label = $user->label;
        $user->delete();

        return back()->with('success', "Akun {$label} dihapus.");
    }
}
