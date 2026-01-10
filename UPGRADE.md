# Upgrade Guide

Panduan ini menjelaskan proses upgrade aplikasi dari repository Git secara aman dan terstruktur.

## Cara cepat
Jalankan script upgrade dari root project:

```bash
bash scripts/upgrade.sh
```

## Mekanisme script upgrade
Script `scripts/upgrade.sh` menjalankan langkah berikut:
1. Memastikan repo Git valid dan tidak ada perubahan *tracked* yang belum disimpan.
2. `git fetch --prune` dari `origin`.
3. `git pull --ff-only` untuk menghindari merge otomatis.
4. `composer install` dengan `--no-dev` dan autoloader optimize.
5. `php artisan migrate --force`.
6. `php artisan optimize` untuk refresh cache config/route/view.

## Prasyarat sebelum upgrade
- Akses jaringan ke GitHub.
- PHP dan Composer tersedia di PATH.
- Database sudah siap dan `.env` valid.

## Langkah upgrade terstruktur (manual)
```bash
git fetch --prune origin
git pull --ff-only origin <branch>
COMPOSER_ALLOW_SUPERUSER=1 php /usr/local/bin/composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
php artisan migrate --force
php artisan optimize
```

## Variabel opsional
- `COMPOSER_BIN` untuk override path composer (default `composer`).
- `PHP_BIN` untuk override binary PHP (default `php`).

Contoh:
```bash
COMPOSER_BIN=/usr/local/bin/composer PHP_BIN=/usr/bin/php8.4 bash scripts/upgrade.sh
```

## Saran operasional
- Lakukan backup database sebelum upgrade besar.
- Jalankan upgrade di jam low traffic.
- Cek halaman login dan admin setelah upgrade.

## Catatan
- Jika ada perubahan lokal pada file *tracked*, simpan dulu (commit/stash) sebelum upgrade.
- Untuk environment production, pastikan service PHP-FPM dan Nginx tetap berjalan normal setelah upgrade.
