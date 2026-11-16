# Pendekin: URL Shortener (Laravel)

## Konteks
Proyek tugas besar Komputasi Awan (CI/CD Jenkins + Docker). Aplikasi ini akan di-container-kan dan di-deploy
lewat pipeline Jenkins, lalu dipakai lagi untuk deploy ke Azure. Jadi kode harus stateless, konfigurasi
lewat environment variable, dan mudah dites.

## Stack
- Laravel versi terbaru (project sudah di-init), PHP sesuai versi yang didukung Laravel tersebut
- REST API (JSON) dengan Laravel Sanctum (token) untuk autentikasi
- Database: MySQL (migrasi + seeder wajib)
- Cache: Redis (dipakai untuk cache pemetaan kode -> URL; klien: predis/predis agar tidak perlu ekstensi PHP tambahan)
- Testing: Pest atau PHPUnit (pilih salah satu dan konsisten). Test memakai SQLite in-memory dan CACHE_STORE=array
  sehingga bisa jalan di dalam Docker tanpa MySQL/Redis.

## Fitur wajib
1. Buat link pendek dari URL panjang: kode acak 6 karakter, atau alias kustom
2. Redirect: GET /{kode}
3. Daftar link milik pengguna yang login
4. Statistik jumlah klik per hari untuk sebuah link
5. Nonaktifkan link
6. Endpoint GET /health: 200 + status koneksi database; 503 jika database tidak terjangkau

## Aturan bisnis (WAJIB divalidasi di server dan dites)
- Alias kustom: unik, hanya huruf/angka/tanda hubung (^[A-Za-z0-9-]+$), panjang 3-30, tidak boleh memakai kata
  yang bentrok dengan route (health, api, login, register, dll.)
- URL tujuan wajib valid dan hanya http/https
- Link nonaktif atau lewat expires_at -> respons 410 Gone (bukan redirect)
- Kode tidak ditemukan -> 404
- Redirect sukses -> 302 dengan header Location

## Konvensi kode
- Business logic di Service class (app/Services), bukan di controller
- Validasi lewat Form Request
- Respons API lewat API Resource
- Gunakan Eloquent, migration, factory, dan seeder; tidak ada query mentah kecuali perlu
- Route catch-all GET /{code} harus didaftarkan PALING AKHIR dan dibatasi regex agar tidak menabrak /health dan /api/*
- Semua konfigurasi (DB, Redis, APP_URL, dll.) dari .env; jangan hardcode kredensial; .env di .gitignore, sediakan .env.example
- Cache pemetaan kode -> data link di Redis dengan TTL; invalidasi cache saat link dinonaktifkan atau diubah
- Kode harus rapi untuk lint (Laravel Pint) dan ditulis dengan type hint
- Bahasa pesan error/validasi: Indonesia