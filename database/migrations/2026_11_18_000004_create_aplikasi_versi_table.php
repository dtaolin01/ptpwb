<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Versi APK aplikasi Android (Supir & Checker) yang diunggah admin.
     * Aplikasi mengecek versi terbaru di sini lalu memperbarui dirinya sendiri.
     */
    public function up(): void
    {
        Schema::create('aplikasi_versi', function (Blueprint $table) {
            $table->id();
            $table->string('aplikasi', 20);                  // supir | checker
            $table->unsignedInteger('version_code');          // angka versi (harus naik)
            $table->string('version_name', 30);               // mis. 1.2.0
            $table->text('catatan')->nullable();              // apa yang baru
            $table->boolean('wajib')->default(false);         // wajib update (tidak bisa "Nanti")
            $table->string('file');                           // path di storage/app/private
            $table->unsignedBigInteger('ukuran');             // byte
            $table->string('sha256', 64);
            $table->timestamps();

            $table->unique(['aplikasi', 'version_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aplikasi_versi');
    }
};
