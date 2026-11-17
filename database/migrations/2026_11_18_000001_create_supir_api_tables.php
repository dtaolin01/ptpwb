<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * supir_tokens : token login aplikasi supir (disimpan dalam bentuk hash SHA-256).
     * lokasi_truk  : riwayat lokasi HP supir yang dikirim aplikasi tiap 2 jam.
     */
    public function up(): void
    {
        Schema::create('supir_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supir_id')->constrained('supir')->cascadeOnDelete();
            $table->foreignId('truck_id')->constrained('trucks')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('terakhir_dipakai')->nullable();
            $table->timestamps();
        });

        Schema::create('lokasi_truk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained('trucks')->cascadeOnDelete();
            $table->foreignId('pengiriman_id')->nullable()->constrained('pengiriman')->nullOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->float('akurasi')->nullable();          // meter
            $table->timestamp('waktu');                   // waktu lokasi diambil di HP
            $table->timestamps();

            $table->index(['truck_id', 'waktu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lokasi_truk');
        Schema::dropIfExists('supir_tokens');
    }
};
