<?php

use App\Support\Kota;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Titik peta untuk tracking: asal, tujuan, dan posisi truk terakhir.
     */
    public function up(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->string('asal')->nullable();
            $table->decimal('asal_lat', 10, 7)->nullable();
            $table->decimal('asal_lng', 10, 7)->nullable();
            $table->decimal('tujuan_lat', 10, 7)->nullable();
            $table->decimal('tujuan_lng', 10, 7)->nullable();
            $table->decimal('posisi_lat', 10, 7)->nullable();
            $table->decimal('posisi_lng', 10, 7)->nullable();
            $table->dateTime('posisi_update')->nullable();
        });

        // Data lama: isi asal dari lokasi langkah pertama, lalu koordinat dari nama kota
        DB::table('pengiriman')->orderBy('id')->each(function ($p) {
            $asal = DB::table('tracking_events')
                ->where('pengiriman_id', $p->id)
                ->orderBy('urutan')
                ->value('lokasi');

            $titikAsal = Kota::cari($asal);
            $titikTujuan = Kota::cari($p->tujuan);

            DB::table('pengiriman')->where('id', $p->id)->update([
                'asal'       => $asal,
                'asal_lat'   => $titikAsal[0] ?? null,
                'asal_lng'   => $titikAsal[1] ?? null,
                'tujuan_lat' => $titikTujuan[0] ?? null,
                'tujuan_lng' => $titikTujuan[1] ?? null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('pengiriman', function (Blueprint $table) {
            $table->dropColumn([
                'asal', 'asal_lat', 'asal_lng', 'tujuan_lat', 'tujuan_lng',
                'posisi_lat', 'posisi_lng', 'posisi_update',
            ]);
        });
    }
};
