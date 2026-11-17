<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Otomatisasi pengiriman:
     * - pelanggan.lat/lng/kota : titik perusahaan tujuan, dicari otomatis dari alamat
     * - pengaturan             : lokasi pabrik & radius deteksi (diatur di menu Lokasi Pabrik)
     * - pengiriman.waktu_*      : kapan truk tiba/keluar pabrik & tiba di tujuan (dari timestamp lokasi HP)
     */
    public function up(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('kota')->nullable();
            $table->string('lokasi_sumber', 20)->nullable();   // otomatis | manual
        });

        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci')->primary();
            $table->text('nilai')->nullable();
            $table->timestamps();
        });

        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dateTime('waktu_tiba_pabrik')->nullable();
            $table->dateTime('waktu_keluar_pabrik')->nullable();
            $table->dateTime('waktu_tiba_tujuan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dropColumn(['waktu_tiba_pabrik', 'waktu_keluar_pabrik', 'waktu_tiba_tujuan']);
        });

        Schema::dropIfExists('pengaturan');

        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng', 'kota', 'lokasi_sumber']);
        });
    }
};
