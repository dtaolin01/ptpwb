<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Daftar barang tersimpan untuk isian surat jalan (tidak perlu diketik ulang). */
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->unique();
            $table->string('kode', 50)->nullable();
            $table->string('satuan', 20)->nullable();
            $table->unsignedInteger('dipakai')->default(0);   // untuk mengurutkan yang paling sering
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
