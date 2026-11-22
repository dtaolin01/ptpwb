<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto bukti pengiriman dari aplikasi supir.
     * Gambarnya disimpan sebagai FILE di storage/app/private/bukti (bukan di database);
     * tabel ini hanya berisi nama file & ukurannya.
     */
    public function up(): void
    {
        Schema::create('bukti_foto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengiriman_id')->constrained('pengiriman')->cascadeOnDelete();
            $table->string('path', 191);
            $table->unsignedInteger('ukuran');      // byte
            $table->unsignedSmallInteger('lebar');
            $table->unsignedSmallInteger('tinggi');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukti_foto');
    }
};
