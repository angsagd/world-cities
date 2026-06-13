# World Cities API

API referensi geografi berbasis PHP native dan MySQL, tanpa framework,
autentikasi, atau rate limiting. API menyediakan data region, subregion,
negara, state/provinsi, dan kota dalam format JSON.

Data geografi diadaptasi dari
[dr5hn/countries-states-cities-database](https://github.com/dr5hn/countries-states-cities-database).

## Persyaratan

- PHP 8.2 atau lebih baru
- Ekstensi PHP `pdo_mysql`, `mbstring`, dan `json`
- MySQL 8 atau versi kompatibel
- Apache dengan `mod_rewrite` untuk deployment shared hosting

Tidak ada dependency Composer atau proses build.

## Konfigurasi Database

Salin konfigurasi contoh:

```bash
cp config/database.example.php config/database.php
```

Kemudian sesuaikan koneksi MySQL di `config/database.php`. File konfigurasi
tersebut diabaikan oleh Git agar kredensial tidak masuk repository.

Konfigurasi juga dapat diganti menggunakan environment variable:

```text
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
DB_SOCKET
```

Jika `DB_SOCKET` diisi, koneksi menggunakan Unix socket. Jika tidak, koneksi
menggunakan host dan port.

Database dan tabel harus sudah tersedia. Project ini tidak menyediakan migrasi
atau seeder. Directory lokal `database/` dan `specification/` juga diabaikan
oleh Git.

## Endpoint

### Regions

```text
GET /api/regions
GET /api/regions/{id}
GET /api/regions/{id}/subregions
```

### Subregions

```text
GET /api/subregions
GET /api/subregions/{id}
GET /api/subregions/{id}/countries
```

### Countries

```text
GET /api/countries
GET /api/countries/{id}
GET /api/countries/{id}/states
GET /api/countries/search/{keyword}
```

### States

```text
GET /api/states
GET /api/states/{id}
GET /api/states/{id}/cities
GET /api/states/search/{keyword}
```

### Cities

```text
GET /api/cities/{id}
GET /api/cities/search/{keyword}
```

`GET /api/cities` belum diimplementasikan karena dapat menghasilkan response
yang sangat besar. Untuk sementara endpoint tersebut menghasilkan `200 []`.

## Perilaku API

- Semua response menggunakan JSON array dan HTTP status `200`.
- Data atau parameter yang tidak ditemukan menghasilkan `[]`.
- Route dan method yang tidak didukung menghasilkan `[]`.
- CORS publik diaktifkan dengan `Access-Control-Allow-Origin: *`.
- Endpoint biasa menggunakan cache publik 24 jam.
- Endpoint pencarian menggunakan cache publik 1 jam.
- Error internal dicatat ke `storage/logs/application.log`.
- Error database dan exception tidak ditampilkan kepada client.

Pencarian dengan keyword 1 sampai 3 karakter menggunakan exact match.
Keyword lebih dari 3 karakter menggunakan pencarian `LIKE '%keyword%'`.
Hasil pencarian dibatasi maksimal 100 baris.

Hasil `GET /api/cities/search/{keyword}` memuat seluruh field city serta field
tambahan `state_name` dan `country_name`.

Contoh:

```json
[
  {
    "id": 56428,
    "state_id": 1822,
    "name": "Denpasar",
    "latitude": "-8.65000000",
    "longitude": "115.21667000",
    "timezone_id": 239,
    "state_name": "Bali",
    "country_name": "Indonesia"
  }
]
```

## Menjalankan Secara Lokal

Jalankan dari root project:

```bash
php -S 127.0.0.1:8088 index.php
```

Kemudian buka:

```text
http://127.0.0.1:8088/api/regions
http://127.0.0.1:8088/api/countries
http://127.0.0.1:8088/api/cities/search/denpasar
```

## Pengujian

Test integrasi memerlukan database yang sudah terisi dan konfigurasi koneksi
yang valid:

```bash
php tests/integration.php
```

## Shared Hosting

Unggah source code ke directory aplikasi, lalu pastikan:

1. PHP menggunakan versi 8.2 atau lebih baru.
2. Ekstensi PHP yang diperlukan sudah aktif.
3. Apache mengizinkan `.htaccess` dan `mod_rewrite`.
4. `storage/logs` dapat ditulis oleh proses PHP.
5. `config/database.php` berisi kredensial database hosting.
6. Database dan seluruh data geografi sudah diimpor secara terpisah.
7. Debug dan tampilan error PHP dinonaktifkan di production.

`.htaccess` mengarahkan request ke `index.php` dan memblokir akses HTTP langsung
ke directory internal seperti `config`, `src`, `storage`, `database`, dan
`specification`.

Jika hosting mendukung pengaturan document root, letakkan source aplikasi di
luar `public_html` dan ekspos hanya front controller serta aturan rewrite.

## Lisensi dan Sumber Data

Kode API dalam repository ini dan dataset sumber memiliki kepemilikan serta
ketentuan lisensi masing-masing. Untuk penggunaan atau redistribusi data,
periksa lisensi terbaru pada repository sumber:

[dr5hn/countries-states-cities-database](https://github.com/dr5hn/countries-states-cities-database)
