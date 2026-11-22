<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tanda tangan online penerima di surat jalan. */
    public function up(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->string('ttd_nama', 100)->nullable();
            $table->longText('ttd_gambar')->nullable();   // PNG base64 (data URL)
            $table->timestamp('ttd_at')->nullable();
            $table->string('ttd_ip', 45)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dropColumn(['ttd_nama', 'ttd_gambar', 'ttd_at', 'ttd_ip']);
        });
    }
};
