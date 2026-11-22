<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Isian surat jalan: penjual & daftar barang (nama, kode, qty, satuan). */
    public function up(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->string('penjual', 150)->nullable();
            $table->json('barang')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dropColumn(['penjual', 'barang']);
        });
    }
};
