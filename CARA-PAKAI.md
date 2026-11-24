# Cara memakai paket ini (sekitar 10 menit)

Paket ini berisi kode modul kurir. Project Laravel-nya belum ada di dalamnya, jadi kamu buat dulu di laptopmu,
lalu salin isi folder `overlay/` ke dalamnya.

## 1. Buat project Laravel baru

Butuh PHP 8.2+ dan Composer (Laravel Herd yang kamu punya sudah cukup).

```bash
composer create-project laravel/laravel courier-api
cd courier-api
```

## 2. Salin file dari paket ini

Dari folder tempat zip ini diekstrak (`overlay/` ada di sebelah file ini):

```bash
cp -R /path/ke/paket/overlay/. .
```

Ini menambah model, migration, controller, request, resource, factory, seeder, `routes/api.php`, semua test,
dan menimpa `README.md` bawaan Laravel dengan dokumentasi modul kurir.

## 3. Aktifkan route API (2 perubahan kecil di `bootstrap/app.php`)

Laravel baru belum memuat `routes/api.php`. Buka `bootstrap/app.php`, lalu:

a. Tambahkan baris `api:` di `withRouting`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',      // <-- tambah ini
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

b. Isi `withExceptions` supaya error di `/api/*` selalu berbentuk JSON (404 dan 422 tidak berupa halaman HTML):

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
    );
})
```

dan tambahkan `use Illuminate\Http\Request;` di bagian atas file.

## 4. Jalankan test dulu

```bash
php artisan test
```

Semua test seharusnya hijau. Test memakai SQLite di memori (butuh ekstensi `pdo_sqlite`), jadi tidak menyentuh
database MySQL-mu.

**Penting:** saya tidak bisa menjalankan Laravel di tempat saya bekerja (Packagist diblokir), jadi test ini hanya
saya cek sintaksnya (`php -l`), belum dijalankan. Kalau ada test yang merah, kirim pesan errornya ke saya dan akan
saya perbaiki.

## 5. Coba dengan MySQL

Isi `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=courier_api
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `courier_api`, lalu:

```bash
php artisan migrate
php artisan db:seed --class=CourierSeeder
php artisan serve
```

Coba di Postman (header `Accept: application/json`):

- `GET http://127.0.0.1:8000/api/couriers`
- `GET http://127.0.0.1:8000/api/couriers?search=budi+agung`
- `GET http://127.0.0.1:8000/api/couriers?level=2,3&sort=registered_at`

## 6. Upload ke GitHub (public)

Dengan GitHub CLI:

```bash
git init
git add .
git commit -m "Add courier CRUD API with tests"
gh repo create courier-api --public --source=. --push
```

atau buat repository kosong yang public di github.com, lalu:

```bash
git remote add origin https://github.com/<username-kamu>/courier-api.git
git branch -M main
git push -u origin main
```

Terakhir, buka `README.md` dan ganti `<repository-url>` dengan link repository-mu, lalu tempel linknya di kolom
yang diminta pada tes.

## Sebelum mengirim, pastikan kamu paham kodenya

Tes ini dinilai juga lewat wawancara. Pastikan kamu bisa menjelaskan: kenapa pencarian memakai scope di model,
kenapa `id` ditambahkan sebagai pengurut kedua, kenapa query string divalidasi di `IndexCourierRequest`, dan kenapa
`levels()` memakai `whereIn`. Penjelasannya ada di bagian "Design choices" di `README.md`.
