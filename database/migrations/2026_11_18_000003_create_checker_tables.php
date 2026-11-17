<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aplikasi Checker (petugas di pabrik):
     * - checker            : akun petugas, login dengan nomor telepon + PIN (dibuat admin)
     * - checker_tokens     : token login aplikasi checker (hash SHA-256)
     * - notifikasi_pabrik  : kejadian truk mendekati / tiba di / keluar dari pabrik
     */
    public function up(): void
    {
        Schema::create('checker', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('telepon')->unique();
            $table->string('pin');                           // hash
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('checker_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checker_id')->constrained('checker')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('terakhir_dipakai')->nullable();
            $table->timestamps();
        });

        Schema::create('notifikasi_pabrik', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);                     // mendekati | tiba | keluar
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete();
            $table->foreignId('pengiriman_id')->nullable()->constrained('pengiriman')->nullOnDelete();
            $table->string('plat')->nullable();
            $table->string('nama_supir')->nullable();
            $table->string('judul');
            $table->string('pesan');
            $table->unsignedInteger('jarak_m')->nullable();
            $table->timestamp('waktu');                       // waktu lokasi dari HP supir
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_pabrik');
        Schema::dropIfExists('checker_tokens');
        Schema::dropIfExists('checker');
    }
};
