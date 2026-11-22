<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Catatan pengiriman surat jalan otomatis lewat WhatsApp ke pelanggan. */
    public function up(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->timestamp('surat_jalan_wa_at')->nullable();      // terakhir berhasil terkirim
            $table->string('surat_jalan_wa_status', 255)->nullable(); // "Terkirim ke 0812…" / alasan gagal
        });
    }

    public function down(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dropColumn(['surat_jalan_wa_at', 'surat_jalan_wa_status']);
        });
    }
};
