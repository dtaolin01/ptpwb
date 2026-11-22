<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Keterangan foto bukti: kapan diambil, alamat lokasi, dan asal lokasinya. */
    public function up(): void
    {
        Schema::table('bukti_foto', function (Blueprint $table) {
            $table->timestamp('waktu_foto')->nullable();
            $table->string('alamat', 255)->nullable();
            $table->string('sumber_lokasi', 10)->nullable();   // hp | truk
        });
    }

    public function down(): void
    {
        Schema::table('bukti_foto', function (Blueprint $table) {
            $table->dropColumn(['waktu_foto', 'alamat', 'sumber_lokasi']);
        });
    }
};
