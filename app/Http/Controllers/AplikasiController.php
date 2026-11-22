<?php

namespace App\Http\Controllers;

use App\Models\AplikasiVersi;
use App\Support\InfoApk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Menu admin "Update Aplikasi": unggah APK baru aplikasi Supir / Checker.
 * Aplikasi di HP mengecek versi terbaru dan memperbarui dirinya sendiri.
 */
class AplikasiController extends Controller
{
    public function index()
    {
        $versi = AplikasiVersi::orderBy('aplikasi')->orderByDesc('version_code')->get()->groupBy('aplikasi');

        return view('aplikasi', [
            'daftar'  => AplikasiVersi::APLIKASI,
            'versi'   => $versi,
            'terbaru' => collect(AplikasiVersi::APLIKASI)->mapWithKeys(fn ($n, $k) => [$k => AplikasiVersi::terbaru($k)]),
        ]);
    }

    public function store(Request $request)
    {
        $aplikasi = (string) $request->input('aplikasi');
        $terakhir = (int) AplikasiVersi::where('aplikasi', $aplikasi)->max('version_code');

        $data = $request->validate([
            'aplikasi'     => ['required', Rule::in(array_keys(AplikasiVersi::APLIKASI))],
            'file'         => ['required', 'file', 'max:204800'],    // 200 MB
            // Biasanya dibaca otomatis dari APK; isian manual hanya dipakai kalau APK tidak bisa dibaca
            'version_code' => ['nullable', 'integer', 'min:1'],
            'version_name' => ['nullable', 'string', 'max:30', 'regex:/^[0-9A-Za-z.\-]+$/'],
            'catatan'      => ['nullable', 'string', 'max:2000'],
            'wajib'        => ['nullable', 'boolean'],
        ], [
            'version_name.regex' => 'Version name hanya angka, huruf, titik, dan strip. Contoh: 1.2.0',
            'file.max'           => 'Ukuran APK maksimal 200 MB.',
        ], [
            'file'         => 'file APK',
            'version_code' => 'version code',
            'version_name' => 'version name',
        ]);

        $file = $request->file('file');
        $data['version_name'] = preg_replace('/[^0-9A-Za-z.\-]+/', '-', (string) ($data['version_name'] ?? ''));

        // Pastikan benar-benar APK (berkas zip berakhiran .apk)
        $awal = (string) file_get_contents($file->getRealPath(), false, null, 0, 2);
        if (strtolower($file->getClientOriginalExtension()) !== 'apk' || $awal !== 'PK') {
            return back()->withInput()->withErrors(['file' => 'File harus berupa APK (.apk).']);
        }

        // Versi diambil dari dalam APK, supaya sama persis dengan aplikasi yang terpasang di HP.
        // (Kalau angka di website lebih besar dari versi APK, HP akan terus menawarkan pembaruan.)
        $apk = InfoApk::baca($file->getRealPath());
        if ($apk && $apk['version_code']) {
            $pkg = (string) $apk['package'];
            if ($pkg !== '' && AplikasiVersi::dariPackage($pkg) !== $data['aplikasi']) {
                return back()->withInput()->withErrors(['file' =>
                    "APK ini adalah aplikasi {$pkg}, bukan ".AplikasiVersi::APLIKASI[$data['aplikasi']].'. Periksa pilihan aplikasi atau file APK-nya.']);
            }
            $data['version_code'] = (int) $apk['version_code'];
            $data['version_name'] = $apk['version_name'] ?: ($data['version_name'] ?? (string) $apk['version_code']);
        } elseif (empty($data['version_code']) || empty($data['version_name'])) {
            return back()->withInput()->withErrors(['version_code' =>
                'Versi tidak bisa dibaca dari APK. Isi version code dan version name sesuai build.gradle.']);
        }

        if ($data['version_code'] <= $terakhir) {
            return back()->withInput()->withErrors(['file' =>
                "APK ini versionCode {$data['version_code']}, padahal versi terakhir di website sudah {$terakhir}. "
                ."Naikkan versionCode di build.gradle (mis. ".($terakhir + 1).'), build ulang, lalu unggah lagi.']);
        }

        $path = $file->storeAs('aplikasi', $data['aplikasi'].'-'.$data['version_code'].'-'.$data['version_name'].'.apk', 'local');

        AplikasiVersi::create([
            'aplikasi'     => $data['aplikasi'],
            'version_code' => $data['version_code'],
            'version_name' => $data['version_name'],
            'catatan'      => $data['catatan'] ?? null,
            'wajib'        => $request->boolean('wajib'),
            'file'         => $path,
            'ukuran'       => $file->getSize(),
            'sha256'       => hash_file('sha256', $file->getRealPath()),
        ]);

        return redirect()->route('aplikasi')->with('success',
            AplikasiVersi::APLIKASI[$data['aplikasi']].' versi '.$data['version_name'].' berhasil diunggah. HP akan memperbarui saat aplikasi dibuka.');
    }

    public function destroy(AplikasiVersi $versi)
    {
        Storage::disk('local')->delete($versi->file);
        $versi->delete();

        return redirect()->route('aplikasi')->with('success', 'Versi '.$versi->version_name.' dihapus.');
    }

    /* ---------- Publik (tanpa login) ---------- */

    /** /unduh/supir → APK terbaru (untuk pemasangan pertama & pembaruan). */
    public function unduh(string $aplikasi, ?int $id = null)
    {
        abort_unless(isset(AplikasiVersi::APLIKASI[$aplikasi]), 404);

        $v = $id
            ? AplikasiVersi::where('aplikasi', $aplikasi)->findOrFail($id)
            : AplikasiVersi::terbaru($aplikasi);

        abort_unless($v && Storage::disk('local')->exists($v->file), 404, 'Belum ada APK yang diunggah.');

        return Storage::disk('local')->download($v->file, $v->nama_file, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }

    /** API: GET /api/aplikasi/{aplikasi}/versi → versi terbaru. */
    public function versi(string $aplikasi)
    {
        abort_unless(isset(AplikasiVersi::APLIKASI[$aplikasi]), 404);

        $v = AplikasiVersi::terbaru($aplikasi);

        if (! $v) {
            return response()->json(['ada' => false]);
        }

        return response()->json([
            'ada'          => true,
            'version_code' => $v->version_code,
            'version_name' => $v->version_name,
            'catatan'      => $v->catatan,
            'wajib'        => $v->wajib,
            'ukuran'       => $v->ukuran,
            'sha256'       => $v->sha256,
            'url'          => route('unduh.aplikasi', ['aplikasi' => $aplikasi, 'id' => $v->id]),
            'tanggal'      => $v->created_at?->locale('id')->translatedFormat('d M Y'),
        ]);
    }
}
