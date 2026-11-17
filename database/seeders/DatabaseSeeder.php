<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Isi database: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        // Akun admin default untuk login di /admin (ganti password setelah login pertama)
        User::firstOrCreate(
            ['username' => 'admin'],
            ['email' => 'admin@pwb.test', 'password' => 'password'],
        );

        $this->call([
            ServiceSeeder::class,
            ContactSeeder::class,
            PwbSeeder::class,
        ]);
    }
}
