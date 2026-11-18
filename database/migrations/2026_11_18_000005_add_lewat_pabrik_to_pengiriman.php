<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rute pengiriman:
     *   lewat_pabrik = true  → muat dulu di pabrik (Menuju Pabrik → Muat → Pengiriman)
     *   lewat_pabrik = false → dalam kota, langsung ke tujuan (tanpa tahap pabrik)
     * Data lama tetap "lewat pabrik" seperti sebelumnya.
     */
    public function up(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->boolean('lewat_pabrik')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dropColumn('lewat_pabrik');
        });
    }
};
