<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan kapan & ke mana laporan PDF terakhir dikirim ke pelanggan
     * (hanya admin yang bisa mengirim, dari halaman Laporan untuk Pelanggan),
     * dan nomor WhatsApp pelanggan sebagai tujuan kirim laporan.
     */
    public function up(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->timestamp('laporan_dikirim_at')->nullable();
            $table->string('laporan_dikirim_ke')->nullable();
        });

        Schema::table('pelanggan', function (Blueprint $table) {
            $table->string('telepon', 30)->nullable()->after('kontak');
        });
    }

    public function down(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dropColumn(['laporan_dikirim_at', 'laporan_dikirim_ke']);
        });

        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropColumn('telepon');
        });
    }
};
