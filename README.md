# Resepku API

REST API dan database untuk aplikasi Flutter **Resepku**, aplikasi berbagi resep masakan.
Dibangun dengan Laravel 12, MySQL, dan Laravel Sanctum untuk autentikasi token.

## Anggota Kelompok

| No | Nama | NIM |
|---|---|---|
| 1 | NAMA_ANGGOTA_1 | NIM_1 |
| 2 | NAMA_ANGGOTA_2 | NIM_2 |
| 3 | NAMA_ANGGOTA_3 | NIM_3 |
| 4 | NAMA_ANGGOTA_4 | NIM_4 |

## Fitur

- Registrasi, login, dan logout dengan token Bearer (Sanctum)
- Profil pengguna: ubah data diri, foto profil, dan kata sandi
- CRUD resep beserta bahan, langkah memasak, dan upload foto
- Pencarian dan filter resep berdasarkan kategori, waktu masak, dan urutan
- Favorit resep
- Rating resep (1-5), rata-rata dihitung otomatis
- Kategori dan banner untuk halaman beranda

## Kebutuhan

- PHP 8.2 atau lebih baru
- Composer
- MySQL / MariaDB (misalna lyewat Laragon atau XAMPP)

## Instalasi

```bash
git clone https://github.com/<username>/resepku-api.git
cd resepku-api

composer install
cp .env.example .env
php artisan key:generate
```

Buat database kosong bernama `resepku` di MySQL, lalu sesuaikan bagian ini di `.env` bila perlu:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=resepku
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migration, seeder, dan link folder penyimpanan gambar:

```bash
php artisan migrate --seed
php artisan storage:link
```

Untuk mengulang database dari awal: `php artisan migrate:fresh --seed`.

## Menjalankan Server

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

API dapat diakses di `http://127.0.0.1:8000/api`.

| Dijalankan dari | Base URL |
|---|---|
| Browser / Flutter Web | `http://127.0.0.1:8000/api` |
| Emulator Android | `http://10.0.2.2:8000/api` |
| HP fisik (satu jaringan Wi-Fi) | `http://<IP-laptop>:8000/api` |

Pengguna Laragon juga bisa memakai `http://resepku-api.test/api` setelah menekan **Reload** di Laragon.

### Akun Contoh

Semua akun hasil seeder memakai kata sandi `password`:

`sari.wulandari@email.com`, `budi@email.com`, `siti@email.com`, `rina@email.com`,
`agus@email.com`, `dewi@email.com`

## Struktur Database

Script migration ada di [`database/migrations`](database/migrations), data awal di
[`database/seeders`](database/seeders).

| Tabel | Isi |
|---|---|
| `users` | Akun pengguna beserta profil (phone, address, birth_date, photo, avatar_index) |
| `categories` | Kategori resep |
| `recipes` | Resep: pemilik, kategori, nama, gambar, waktu masak, rating |
| `recipe_ingredients` | Bahan tiap resep |
| `recipe_steps` | Langkah memasak tiap resep |
| `favorites` | Resep favorit pengguna |
| `recipe_ratings` | Rating 1-5 dari pengguna untuk resep |
| `banners` | Banner carousel di beranda |
| `personal_access_tokens` | Token login Sanctum |

## Daftar Endpoint

Endpoint bertanda 🔒 membutuhkan header `Authorization: Bearer <token>` yang didapat dari `/api/login`.
Semua request sebaiknya menyertakan header `Accept: application/json`.

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/register` | Daftar akun baru |
| POST | `/api/login` | Login, mengembalikan token |
| POST | `/api/logout` 🔒 | Logout |
| GET | `/api/me` 🔒 | Profil dan statistik pengguna |
| PUT | `/api/me` 🔒 | Ubah profil |
| GET | `/api/me/recipes` 🔒 | Resep milik pengguna |
| GET | `/api/categories` | Daftar kategori |
| GET | `/api/banners` | Daftar banner beranda |
| GET | `/api/recipes` | Daftar resep (filter: `category`, `search`, `max_time`, `sort`) |
| GET | `/api/recipes/{id}` | Detail resep |
| POST | `/api/recipes` 🔒 | Tambah resep |
| PUT | `/api/recipes/{id}` 🔒 | Ubah resep (hanya pemilik) |
| DELETE | `/api/recipes/{id}` 🔒 | Hapus resep (hanya pemilik) |
| GET | `/api/favorites` 🔒 | Daftar resep favorit |
| POST | `/api/recipes/{id}/favorite` 🔒 | Tambah ke favorit |
| DELETE | `/api/recipes/{id}/favorite` 🔒 | Hapus dari favorit |
| POST | `/api/recipes/{id}/rating` 🔒 | Beri rating 1-5 |

Contoh body request, format respons, dan aturan validasi lengkap ada di [API.md](API.md).

## Struktur Folder Penting

```
app/Http/Controllers/Api/   Controller REST API
app/Models/                 Model Eloquent
routes/api.php              Definisi endpoint
database/migrations/        Script migration database
database/seeders/           Data awal (kategori, resep, akun contoh)
API.md                      Dokumentasi API lengkap
```
