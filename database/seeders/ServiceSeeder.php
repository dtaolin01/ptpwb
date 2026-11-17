<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Data halaman /services.
     * category 'service' -> kartu keunggulan, category 'fleet' -> kartu jenis armada.
     */
    public function run(): void
    {
        $services = [
            [
                'title'       => 'Armada Terawat',
                'description' => 'Setiap unit diperiksa dan dirawat berkala agar selalu siap beroperasi.',
            ],
            [
                'title'       => 'Pengiriman Tepat Waktu',
                'description' => 'Penjadwalan yang terencana agar material tiba sesuai kebutuhan di lokasi.',
            ],
            [
                'title'       => 'Pengemudi Berpengalaman',
                'description' => 'Dikemudikan oleh pengemudi terlatih yang memahami rute dan standar keselamatan.',
            ],
            [
                'title'       => 'Kapasitas Sesuai Kebutuhan',
                'description' => 'Pilih armada yang sesuai dengan volume pengiriman proyek Anda.',
            ],
        ];

        $fleets = [
            [
                'title'        => 'Truk PS',
                'description'  => 'Cocok untuk pengiriman barang skala kecil.',
                'image'        => 'services_pwb.png',
                'suitable_for' => 'Pengiriman material dalam jumlah kecil.',
                'capacity'     => 'Hingga 4 ton',
                'advantage'    => 'Fleksibel dan mudah menjangkau area proyek.',
            ],
            [
                'title'        => 'Truk 6 Roda',
                'description'  => 'Cocok untuk pengiriman barang skala menengah.',
                'image'        => 'services_pwb.png',
                'suitable_for' => 'Pengiriman material proyek skala menengah.',
                'capacity'     => 'Hingga 8 ton',
                'advantage'    => 'Kapasitas lebih besar dan stabil.',
            ],
            [
                'title'        => 'Tronton (Truk 10 Roda)',
                'description'  => 'Cocok untuk pengiriman barang skala besar.',
                'image'        => 'services_pwb.png',
                'suitable_for' => 'Pengiriman material proyek skala besar.',
                'capacity'     => 'Hingga 20 ton',
                'advantage'    => 'Mampu mengangkut material dalam jumlah besar.',
            ],
        ];

        foreach ($services as $row) {
            Service::updateOrCreate(['title' => $row['title'], 'category' => 'service'], $row);
        }

        foreach ($fleets as $row) {
            Service::updateOrCreate(['title' => $row['title'], 'category' => 'fleet'], $row);
        }
    }
}
