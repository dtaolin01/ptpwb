<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel shipments* tidak dipakai lagi: halaman /tracking kini membaca
     * tabel pengiriman + tracking_events yang dikelola dari admin.
     */
    public function up(): void
    {
        Schema::dropIfExists('shipment_locations');
        Schema::dropIfExists('shipment_details');
        Schema::dropIfExists('shipment_steps');
        Schema::dropIfExists('shipments');
    }

    public function down(): void
    {
        // Tidak dikembalikan.
    }
};
