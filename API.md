# Resepku API

Backend Laravel 12 + MySQL (Laragon) untuk aplikasi Flutter Resepku.

## Menjalankan

```bash
composer install
cp .env.example .env   # sudah berisi konfigurasi MySQL Laragon (database "resepku")
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
```

Akses lewat Laragon (`http://resepku-api.test/api`, klik **Reload** di Laragon sekali agar
virtual host dibuat) atau `php artisan serve --host=0.0.0.0 --port=8000`.

Dari emulator Android gunakan `http://10.0.2.2:8000/api`; dari HP fisik gunakan IP laptop
di jaringan yang sama, mis. `http://192.168.1.10:8000/api`.

Akun seed (kata sandi semua `password`): `sari.wulandari@email.com`, `budi@email.com`,
`siti@email.com`, `rina@email.com`, `agus@email.com`, `dewi@email.com`.

## Struktur database

| Tabel | Isi | Dipakai di layar |
|---|---|---|
| `users` | nama, email, password, phone, address, birth_date, photo, avatar_index | Login, Register, Profil, Beranda (sapaan) |
| `categories` | name, slug, icon, sort_order | Beranda (chip kategori), Cari, Tambah Resep |
| `recipes` | user_id, category_id, name, image, cooking_time_minutes, rating, rating_count | Semua layar resep |
| `recipe_ingredients` | recipe_id, name, amount, position | Detail, Tambah Resep |
| `recipe_steps` | recipe_id, position, description | Detail, Tambah Resep |
| `favorites` | user_id, recipe_id (unik per pasangan) | Favorit, tombol hati, statistik Profil |
| `recipe_ratings` | user_id, recipe_id, score 1-5 (unik per pasangan) | Bintang rating di kartu & Detail |
| `banners` | recipe_id, caption, sort_order, is_active | Carousel Beranda |
| `personal_access_tokens` | token Sanctum | Login/Logout |

Kategori "Semua" tidak disimpan; tambahkan di sisi aplikasi.
`recipes.rating` adalah rata-rata dari `recipe_ratings`, dihitung ulang setiap ada rating baru.

## Konvensi

- Selalu kirim header `Accept: application/json`.
- Endpoint bertanda 🔒 butuh `Authorization: Bearer <token>` dari `/login`.
- Endpoint publik tetap menerima token; jika dikirim, `isFavorite` dan `myRating` ikut terisi.
- Error validasi → `422` dengan `{ message, errors: { field: [pesan] } }` (Bahasa Indonesia).
- `401` token tidak valid, `403` bukan pemilik, `404` data tidak ditemukan.
- Gambar dikembalikan sebagai URL lengkap → tampilkan dengan `Image.network`.

## Endpoint

### Auth

| Method | URL | Body |
|---|---|---|
| POST | `/api/register` | `name` (min 3), `email`, `password` (min 6) |
| POST | `/api/login` | `email`, `password`, `device_name` (opsional) → `{ token, user }` |
| POST | `/api/logout` 🔒 | - |

### Profil

| Method | URL | Keterangan |
|---|---|---|
| GET | `/api/me` 🔒 | `{ user, stats: { myRecipes, favorites, totalRecipes } }` |
| PUT | `/api/me` 🔒 | Semua opsional: `name`, `email`, `phone`, `address`, `birth_date` (YYYY-MM-DD), `avatar_index` (0-5), `password` + `password_confirmation`, `photo` (file, kirim via POST + `_method=PUT`) |
| GET | `/api/me/recipes` 🔒 | Resep milik pengguna |

### Kategori & Banner

| Method | URL | Keterangan |
|---|---|---|
| GET | `/api/categories` | `[{ id, name, slug, icon, recipesCount }]` |
| GET | `/api/banners` | `[{ id, caption, recipe }]` untuk carousel |

### Resep

| Method | URL | Keterangan |
|---|---|---|
| GET | `/api/recipes` | Query opsional: `category` (id/slug/nama), `search`, `max_time`, `user_id`, `sort` (`latest`\|`rating`\|`time`\|`popular`), `per_page` |
| GET | `/api/recipes/{id}` | Detail resep |
| POST | `/api/recipes` 🔒 | Lihat body di bawah |
| PUT | `/api/recipes/{id}` 🔒 | Field sama, semua opsional. Untuk ganti foto: POST + `_method=PUT` (multipart) |
| DELETE | `/api/recipes/{id}` 🔒 | Hanya pemilik |

Body tambah resep (JSON atau multipart):

```json
{
  "name": "Soto Ayam Lamongan",
  "category_id": 2,
  "cooking_time_minutes": 45,
  "image_path": "images/recipes/ayam_geprek.jpg",
  "ingredients": [{ "name": "Ayam", "amount": "1/2 ekor" }],
  "steps": ["Rebus ayam", "Sajikan dengan koya"]
}
```

- `category_id` bisa diganti `category` berisi nama kategori, mis. `"Olahan Ayam"`.
- Foto: kirim file `image` (jpg/png/webp, maks 2 MB) via multipart, **atau** `image_path` untuk
  memakai gambar bawaan di `public/images/recipes/`.
- Multipart: `ingredients[0][name]`, `ingredients[0][amount]`, `steps[0]`, dst.
- `cooking_time_minutes` 5-180 (sama dengan slider di aplikasi).

Contoh respons resep (bentuk sama dengan `RecipeModel` Flutter, plus beberapa field tambahan):

```json
{
  "id": 1,
  "name": "Nasi Goreng Spesial",
  "image": "http://resepku-api.test/images/recipes/nasi_goreng_spesial.jpg",
  "category": "Olahan Nasi",
  "categoryId": 1,
  "cookingTimeMinutes": 20,
  "rating": 4.5,
  "ratingCount": 4,
  "ingredients": [{ "name": "Nasi putih", "amount": "2 piring" }],
  "steps": ["Panaskan minyak, tumis bawang hingga harum"],
  "uploader": { "id": 2, "name": "Budi Santoso", "profileImage": "http://.../budi.jpg", "avatarIndex": 0 },
  "isFavorite": true,
  "myRating": 5,
  "createdAt": "2026-10-06T20:07:52+00:00"
}
```

### Favorit & Rating

| Method | URL | Keterangan |
|---|---|---|
| GET | `/api/favorites` 🔒 | Resep favorit, terbaru di atas |
| POST | `/api/recipes/{id}/favorite` 🔒 | Tambah favorit → `{ isFavorite, favoritesCount }` |
| DELETE | `/api/recipes/{id}/favorite` 🔒 | Hapus favorit |
| POST | `/api/recipes/{id}/rating` 🔒 | `score` 1-5 → `{ rating, ratingCount, myRating }`. Tidak bisa merating resep sendiri |
