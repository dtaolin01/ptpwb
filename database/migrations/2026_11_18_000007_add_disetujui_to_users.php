<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akun admin baru dari halaman Sign Up harus disetujui pemilik (darrentaolin) dulu.
     * Akun yang sudah ada sebelum fitur ini tetap bisa login (dianggap sudah disetujui).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('disetujui')->default(false);
            $table->timestamp('disetujui_at')->nullable();
        });

        DB::table('users')->update(['disetujui' => true, 'disetujui_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['disetujui', 'disetujui_at']);
        });
    }
};
