<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Info kontak yang tampil di halaman Contact (mirip tabel services).
     * category: 'info'     -> kartu info (Telepon, Email, Alamat, Jam Kerja)
     *           'location' -> kartu "Lokasi Kami" (peta + link Google Maps)
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('icon')->nullable();       // phone | email | location | clock
            $table->string('link')->nullable();       // tel:, mailto:, atau URL Google Maps
            $table->text('map_embed')->nullable();    // URL iframe Google Maps (khusus category location)
            $table->string('category')->default('info');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
