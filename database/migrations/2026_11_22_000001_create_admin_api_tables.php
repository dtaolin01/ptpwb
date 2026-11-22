<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aplikasi Android PWB Admin:
     *  - admin_tokens       : sesi login aplikasi (token disimpan dalam bentuk hash)
     *  - admin_notifikasi   : kejadian penting yang dimunculkan sebagai notifikasi di HP admin
     */
    public function up(): void
    {
        Schema::create('admin_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('perangkat', 100)->nullable();
            $table->timestamp('terakhir_dipakai')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);              // status | pesan | akun | gps | terlambat
            $table->string('judul', 150);
            $table->string('isi', 255)->nullable();
            $table->foreignId('pengiriman_id')->nullable()->constrained('pengiriman')->nullOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete();
            $table->timestamps();
            $table->index(['jenis', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifikasi');
        Schema::dropIfExists('admin_tokens');
    }
};
