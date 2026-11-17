# PT Putra Wijaya Berjaya — Website & Admin

Website company profile + panel admin logistik PT Putra Wijaya Berjaya (Laravel 13, PHP ≥ 8.4).

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite      # kalau pakai SQLite (default)
php artisan migrate:fresh --seed
php artisan serve
```

Akun admin dari seeder: **username `admin` / password `password`** (login di `/admin`, segera ganti password).

## Halaman

| URL | Keterangan | Sumber data |
|---|---|---|
| `/` | Home | statis |
| `/services` | Keunggulan layanan & jenis armada | tabel `services` (`category` = `service` / `fleet`) |
| `/contact` | Info kontak, lokasi, form "Kirim Pesan" | tabel `contacts` (`category` = `info` / `location`), pesan masuk ke `contact_messages` |
| `/tracking` | Lacak resi (peta Leaflet + rute jalan) | tabel `pengiriman` + `tracking_events` (dikelola dari admin → Tracking) |
| `/admin` | Login admin | `users` |
| `/dashboard`, `/trackingAdmin`, `/truck-supir`, `/supir`, `/pelanggan`, `/pesan` | Panel admin (wajib login) | `pengiriman`, `tracking_events`, `trucks`, `supir`, `pelanggan`, `contact_messages` |

## Mengubah isi halaman Services / Contact

Isi kartu di kedua halaman diambil dari database, jadi cukup ubah datanya
(lewat `database/seeders/ServiceSeeder.php` & `ContactSeeder.php`, lalu `php artisan db:seed --class=ContactSeeder`).

Kolom tabel `contacts`:

| Kolom | Isi |
|---|---|
| `title` | Judul kartu (mis. "Telepon") |
| `description` | Teks kartu / alamat |
| `icon` | `phone`, `email`, `location`, atau `clock` |
| `link` | `tel:`, `mailto:`, atau URL (opsional) |
| `map_embed` | URL iframe Google Maps (khusus `category = location`) |
| `category` | `info` (kartu atas) atau `location` (kartu "Lokasi Kami") |
| `sort_order` | Urutan tampil |

## Tracking

Data tracking dikelola di **admin → Tracking**: tambah/ubah pengiriman, langkah perjalanan,
dan titik peta (asal, tujuan, posisi truk; klik di peta atau cari nama tempat).
Halaman publik `/tracking` membaca data yang sama, jadi perubahan langsung terlihat.
Rute jalan diambil dari OSRM dan disimpan di cache 30 hari.

## Update di server (Hostinger)

```bash
cd ~/domains/putrawijayaberjaya.com/ptpwb
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
cd .. && cp -a ptpwb/public/. public_html/
sed -i "s#__DIR__.'/../#__DIR__.'/../ptpwb/#g" public_html/index.php
```
