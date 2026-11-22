<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Mendeteksi migrasi yang belum dijalankan (mis. lupa "php artisan migrate" setelah git pull),
 * supaya admin melihat peringatan alih-alih galat 500.
 */
class CekDatabase
{
    /** @return string[] nama migrasi yang belum dijalankan */
    public static function migrasiTertunda(): array
    {
        return Cache::remember('pwb.migrasi_tertunda', 300, function () {
            try {
                $migrator = app('migrator');
                if (! $migrator->repositoryExists()) {
                    return ['(tabel migrations belum ada)'];
                }
                $file = array_keys($migrator->getMigrationFiles([database_path('migrations')]));
                $sudah = $migrator->getRepository()->getRan();

                return array_values(array_diff($file, $sudah));
            } catch (\Throwable) {
                return [];
            }
        });
    }
}
