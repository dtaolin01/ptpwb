<?php

namespace Tests\Feature;

use App\Models\AdminToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SinkronTest extends TestCase
{
    use RefreshDatabase;

    private function tokenUntuk(string $username, bool $disetujui = true): string
    {
        $user = User::create([
            'username'  => $username,
            'email'     => $username.'@contoh.test',
            'password'  => 'rahasia-sekali',
            'disetujui' => $disetujui,
        ]);
        $plain = 'token-'.$username;
        AdminToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'terakhir_dipakai' => now()]);

        return $plain;
    }

    private function pemilik(): string
    {
        config(['app.pemilik' => 'darrentaolin']);

        return $this->tokenUntuk('darrentaolin');
    }

    public function test_tanpa_token_ditolak(): void
    {
        $this->getJson('/api/admin/sinkron')->assertStatus(401);
    }

    public function test_admin_biasa_bukan_pemilik_ditolak(): void
    {
        config(['app.pemilik' => 'darrentaolin']);
        $token = $this->tokenUntuk('adminlain');

        $this->withToken($token)->getJson('/api/admin/sinkron')->assertStatus(403);
    }

    public function test_daftar_tabel_tanpa_tabel_token_dan_kolom_rahasia(): void
    {
        $token = $this->pemilik();

        $res = $this->withToken($token)->getJson('/api/admin/sinkron')->assertOk();

        $tabel = $res->json('tabel');
        $this->assertArrayHasKey('supir', $tabel);
        $this->assertArrayHasKey('pengiriman', $tabel);
        $this->assertArrayHasKey('lokasi_truk', $tabel);
        $this->assertSame('kunci', $tabel['pengaturan']['kunci']);
        foreach (['admin_tokens', 'supir_tokens', 'checker_tokens', 'sessions', 'password_reset_tokens'] as $rahasia) {
            $this->assertArrayNotHasKey($rahasia, $tabel);
        }
        $this->assertNotContains('password', $tabel['users']['kolom']);
        $this->assertNotContains('remember_token', $tabel['users']['kolom']);
        $this->assertNotContains('pin', $tabel['checker']['kolom']);
        $this->assertContains('username', $tabel['users']['kolom']);
    }

    public function test_isi_tabel_tanpa_password_dan_pin(): void
    {
        $token = $this->pemilik();
        DB::table('checker')->insert(['nama' => 'Cek', 'telepon' => '0811', 'pin' => 'rahasia-pin', 'aktif' => 1]);

        $users = $this->withToken($token)->getJson('/api/admin/sinkron?tabel=users')->assertOk()->json('baris');
        $this->assertSame('darrentaolin', $users[0]['username']);
        $this->assertArrayNotHasKey('password', $users[0]);
        $this->assertArrayNotHasKey('remember_token', $users[0]);

        $checker = $this->withToken($token)->getJson('/api/admin/sinkron?tabel=checker')->assertOk()->json('baris');
        $this->assertSame('Cek', $checker[0]['nama']);
        $this->assertArrayNotHasKey('pin', $checker[0]);
    }

    public function test_halaman_demi_halaman_dan_daftar_kunci(): void
    {
        $token = $this->pemilik();
        foreach (['Andri', 'Budi', 'Cahyo'] as $nama) {
            DB::table('supir')->insert(['nama' => $nama, 'telepon' => '08'.strlen($nama), 'created_at' => now(), 'updated_at' => now()]);
        }
        $id = DB::table('supir')->orderBy('id')->pluck('id')->all();

        $hal1 = $this->withToken($token)->getJson('/api/admin/sinkron?tabel=supir&batas=2')->assertOk();
        $this->assertCount(2, $hal1->json('baris'));
        $this->assertSame('Andri', $hal1->json('baris.0.nama'));
        $this->assertEquals($id[1], $hal1->json('berikutnya'));

        $hal2 = $this->withToken($token)->getJson('/api/admin/sinkron?tabel=supir&batas=2&setelah='.$hal1->json('berikutnya'))->assertOk();
        $this->assertCount(1, $hal2->json('baris'));
        $this->assertSame('Cahyo', $hal2->json('baris.0.nama'));
        $this->assertNull($hal2->json('berikutnya'));

        $kunci = $this->withToken($token)->getJson('/api/admin/sinkron?tabel=supir&hanya_kunci=1')->assertOk();
        $this->assertEquals($id, $kunci->json('daftar'));
        $this->assertNull($kunci->json('berikutnya'));
    }

    public function test_pengaturan_memakai_kunci_teks(): void
    {
        $token = $this->pemilik();
        DB::table('pengaturan')->insert([
            ['kunci' => 'pabrik_lat', 'nilai' => '-2.05'],
            ['kunci' => 'pabrik_nama', 'nilai' => 'Pabrik Conch'],
        ]);

        $res = $this->withToken($token)->getJson('/api/admin/sinkron?tabel=pengaturan&setelah=pabrik_lat')->assertOk();
        $this->assertSame(['pabrik_nama'], array_column($res->json('baris'), 'kunci'));
    }

    public function test_tabel_token_dan_tabel_asing_ditolak(): void
    {
        $token = $this->pemilik();

        foreach (['admin_tokens', 'sessions', 'tidak_ada', 'users;drop'] as $t) {
            $this->withToken($token)->getJson('/api/admin/sinkron?tabel='.urlencode($t))->assertStatus(422);
        }
    }

    public function test_tidak_mengubah_data(): void
    {
        $token = $this->pemilik();
        DB::table('supir')->insert(['nama' => 'Andri', 'telepon' => '0811']);
        $sebelum = [DB::table('supir')->count(), DB::table('users')->count(), DB::table('supir')->value('nama')];

        $this->withToken($token)->getJson('/api/admin/sinkron')->assertOk();
        $this->withToken($token)->getJson('/api/admin/sinkron?tabel=supir')->assertOk();
        $this->withToken($token)->getJson('/api/admin/sinkron?tabel=users')->assertOk();

        $this->assertSame($sebelum, [DB::table('supir')->count(), DB::table('users')->count(), DB::table('supir')->value('nama')]);
    }
}
