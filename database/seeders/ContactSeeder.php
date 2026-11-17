<?php

namespace Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Data halaman /contact.
     * category 'info' -> kartu info, category 'location' -> kartu "Lokasi Kami".
     */
    public function run(): void
    {
        $contacts = [
            [
                'title'       => 'Telepon',
                'description' => '+62 852-1881-5858. Hubungi kami pada jam kerja untuk informasi dan pemesanan.',
                'icon'        => 'phone',
                'link'        => 'tel:+6285218815858',
                'category'    => 'info',
                'sort_order'  => 1,
            ],
            [
                'title'       => 'Email',
                'description' => 'putrawijaya.berjaya@gmail.com. Kirim pertanyaan atau permintaan penawaran, kami balas secepatnya.',
                'icon'        => 'email',
                'link'        => 'mailto:putrawijaya.berjaya@gmail.com',
                'category'    => 'info',
                'sort_order'  => 2,
            ],
            [
                'title'       => 'Alamat',
                'description' => 'PT Putra Wijaya Berjaya, Jl. Raya Simpang Pasir Rt 29, Samarinda.',
                'icon'        => 'location',
                'link'        => 'https://maps.app.goo.gl/uhwC71qUUFmsJEHh6',
                'category'    => 'info',
                'sort_order'  => 3,
            ],
            [
                'title'       => 'Jam Kerja',
                'description' => 'Senin - Sabtu, 08.00 - 17.00 WITA.',
                'icon'        => 'clock',
                'link'        => null,
                'category'    => 'info',
                'sort_order'  => 4,
            ],
            [
                'title'       => 'PT Putra Wijaya Berjaya',
                'description' => 'Jl. Raya Simpang Pasir RT 29 Samarinda',
                'icon'        => 'location',
                'link'        => 'https://maps.app.goo.gl/uhwC71qUUFmsJEHh6',
                'map_embed'   => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.63281339515!2d117.15136547545379!3d-0.5525134352653582!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2df681002812841b%3A0x9f693efaaa840768!2sPT.%20PUTRA%20WIJAYA%20BERJAYA%20(%20PWB%20)!5e0!3m2!1sen!2sid!4v1793113402736!5m2!1sen!2sid',
                'category'    => 'location',
                'sort_order'  => 1,
            ],
        ];

        foreach ($contacts as $row) {
            Contact::updateOrCreate(['title' => $row['title'], 'category' => $row['category']], $row);
        }
    }
}
