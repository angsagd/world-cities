# World Cities API

API referensi geografi berbasis PHP native dan MySQL. Implementasi mengikuti
`specification/spesifikasi-api-geografi.md`.

## Persyaratan

- PHP 8.2 atau lebih baru
- Ekstensi PHP: `pdo_mysql`, `mbstring`, dan `json`
- MySQL 8 atau versi kompatibel
- Apache dengan `mod_rewrite` untuk URL tanpa `index.php`

## Konfigurasi

Konfigurasi database lokal berada di `config/database.php`. File tersebut tidak
dimasukkan ke Git. Salin `config/database.example.php` ketika menyiapkan
environment baru.

Nilai konfigurasi dapat diganti melalui environment variable berikut:

```text
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
DB_SOCKET
```

Jika `DB_SOCKET` tidak diisi, koneksi menggunakan host dan port.

## Endpoint

Endpoint yang tersedia:

```text
GET /api/regions
GET /api/regions/{id}
GET /api/regions/{id}/subregions

GET /api/subregions
GET /api/subregions/{id}
GET /api/subregions/{id}/countries

GET /api/countries
GET /api/countries/{id}
GET /api/countries/{id}/states
GET /api/countries/search/{keyword}

GET /api/states
GET /api/states/{id}
GET /api/states/{id}/cities
GET /api/states/search/{keyword}

GET /api/cities/{id}
GET /api/cities/search/{keyword}
```

`GET /api/cities` sengaja belum diimplementasikan. Request tersebut sementara
menghasilkan `200 []`.

Semua endpoint mengembalikan HTTP `200` dan JSON array. Route, parameter, atau
method yang tidak didukung juga menghasilkan `[]`.

## Menjalankan Secara Lokal

```bash
php -S 127.0.0.1:8080 index.php
```

Contoh:

```bash
curl http://127.0.0.1:8080/api/countries/105
```

Test integrasi database dan routing:

```bash
php tests/integration.php
```

## Shared Hosting

Unggah seluruh isi project ke directory aplikasi. Pastikan:

1. Apache mengizinkan `.htaccess` dan `mod_rewrite`.
2. PHP menggunakan versi 8.2 atau lebih baru.
3. `storage/logs` dapat ditulis oleh proses PHP.
4. Kredensial di `config/database.php` sesuai server hosting.
5. Directory `config`, `src`, `storage`, `database`, dan `specification` tidak
   dapat diakses langsung. Aturan ini sudah disediakan di `.htaccess`.

Jika hosting mendukung pengaturan document root, konfigurasi yang lebih aman
adalah menempatkan file aplikasi di luar `public_html` dan mengekspos hanya
front controller serta `.htaccess`.
