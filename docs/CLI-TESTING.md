# PANDUAN PENGUJIAN REST API & SOAP DENGAN CLI (TERMINAL & POWERSHELL)
## SIPUS-Del — Laravel 11 Web API v1

Dokumen ini menyediakan panduan lengkap untuk menjalankan, memverifikasi rute, dan menguji seluruh endpoint REST API dan SOAP SIPUS-Del menggunakan antarmuka baris perintah (CLI) pada sistem operasi Windows (PowerShell) menggunakan `curl.exe`.

> [!IMPORTANT]
> Pada Windows PowerShell, selalu panggil binary eksekusi langsung **`curl.exe`**, bukan alias bawaan PowerShell `curl` (yang merujuk pada cmdlet `Invoke-WebRequest`).

---

## 1. PERSIAPAN LINGKUNGAN & ARTISAN

### 1.1 Instalasi Dependensi
Pastikan PHP (>= 8.2) dan Composer telah terpasang pada sistem:
```powershell
composer install
```

### 1.2 Konfigurasi Environment & Kunci Aplikasi
Salin berkas konfigurasi lingkungan dan buat application key:
```powershell
copy .env.example .env
php artisan key:generate
```

### 1.3 Migrasi & Seeding Basis Data
Jalankan migrasi tabel master, koleksi buku, transaksi peminjaman, serta tabel token Sanctum:
```powershell
php artisan migrate
php artisan db:seed
```

### 1.4 Menjalankan Server Lokal
Jalankan server pengembangan Laravel pada port 8000:
```powershell
php artisan serve
```
Server akan aktif pada: `http://127.0.0.1:8000` (atau `http://localhost:8000`).

---

## 2. MEMERIKSA DAFTAR RUTE API & SOAP

### 2.1 Memeriksa Rute SOAP
```powershell
php artisan route:list --path=soap
```
Output yang diharapkan:
- `GET|HEAD soap/sipus.wsdl`
- `POST soap/sipus`

### 2.2 Memeriksa Rute REST API v1
```powershell
php artisan route:list --path=api
```
Output yang diharapkan:
- `POST api/v1/auth/login`
- `GET|HEAD api/v1/auth/me`
- `POST api/v1/auth/logout`
- `GET|HEAD api/v1/bukus`
- `POST api/v1/bukus`
- `GET|HEAD api/v1/bukus/{id}`
- `PUT api/v1/bukus/{id}`
- `PATCH api/v1/bukus/{id}`
- `DELETE api/v1/bukus/{id}`
- `GET|HEAD api/v1/bukus/{id}/referensi-eksternal`
- `GET|HEAD api/v1/peminjamans`
- `POST api/v1/peminjamans`
- `GET|HEAD api/v1/peminjamans/{id}`
- `PATCH api/v1/peminjamans/{id}/kembalikan`

---

## 3. PENGUJIAN SOAP DENGAN CURL.EXE

### 3.1 Mengambil Dokumen WSDL Kontrak
```powershell
curl.exe -i -X GET http://localhost:8000/soap/sipus.wsdl
```

### 3.2 SOAP Request: Pengecekan Bebas Pustaka Mahasiswa (Valid)
```powershell
curl.exe -i -X POST http://localhost:8000/soap/sipus `
  -H "Content-Type: text/xml; charset=utf-8" `
  -d @"
<?xml version=""1.0"" encoding=""UTF-8""?>
<soapenv:Envelope xmlns:soapenv=""http://schemas.xmlsoap.org/soap/envelope/"" xmlns:sip=""http://sipus.del.ac.id/soap"">
    <soapenv:Header/>
    <soapenv:Body>
        <sip:CheckClearanceStatusRequest>
            <sip:nim_peminjam>12S24012</sip:nim_peminjam>
        </sip:CheckClearanceStatusRequest>
    </soapenv:Body>
</soapenv:Envelope>
"@
```

### 3.3 SOAP Fault: Permintaan dengan XML Malformed
```powershell
curl.exe -i -X POST http://localhost:8000/soap/sipus `
  -H "Content-Type: text/xml; charset=utf-8" `
  -d "<soapenv:Envelope><soapenv:Body><tagTidakTertutup></soapenv:Body>"
```

### 3.4 SOAP Fault: Elemen NIM Tidak Disertakan
```powershell
curl.exe -i -X POST http://localhost:8000/soap/sipus `
  -H "Content-Type: text/xml; charset=utf-8" `
  -d @"
<?xml version=""1.0"" encoding=""UTF-8""?>
<soapenv:Envelope xmlns:soapenv=""http://schemas.xmlsoap.org/soap/envelope/"" xmlns:sip=""http://sipus.del.ac.id/soap"">
    <soapenv:Body>
        <sip:CheckClearanceStatusRequest>
            <sip:field_lain>Data</sip:field_lain>
        </sip:CheckClearanceStatusRequest>
    </soapenv:Body>
</soapenv:Envelope>
"@
```

---

## 4. PENGUJIAN AUTENTIKASI REST API (SANCTUM)

### 4.1 Login Pustakawan & Mendapatkan Bearer Token
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/auth/login `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d "{\"email\":\"pustakawan@del.ac.id\",\"password\":\"password\"}"
```
Simpan nilai token dari respons JSON (contoh: `$TOKEN = "1|abcdef..."`).

### 4.2 Login Gagal (Kata Sandi Salah — 401 RFC 7807)
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/auth/login `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d "{\"email\":\"pustakawan@del.ac.id\",\"password\":\"password-salah\"}"
```

### 4.3 Mengambil Profil Pustakawan Aktif (Me)
```powershell
$TOKEN = "PASTE_TOKEN_ANDA_DI_SINI"
curl.exe -i -X GET http://localhost:8000/api/v1/auth/me `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```

### 4.4 Mengakses Endpoint Terproteksi Tanpa Token (401 RFC 7807)
```powershell
curl.exe -i -X GET http://localhost:8000/api/v1/auth/me `
  -H "Accept: application/json"
```

### 4.5 Logout & Pencabutan Token (Revocation)
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/auth/logout `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```

---

## 5. PENGUJIAN REST API KATALOG BUKU & HATEOAS

### 5.1 GET Katalog Terpaginasi, Pencarian, & Filter
```powershell
# Katalog lengkap (publik)
curl.exe -i -X GET "http://localhost:8000/api/v1/bukus?per_page=5" -H "Accept: application/json"

# Pencarian judul / penulis
curl.exe -i -X GET "http://localhost:8000/api/v1/bukus?search=Pemrograman" -H "Accept: application/json"

# Filter hanya buku yang memiliki stok (tersedia=1)
curl.exe -i -X GET "http://localhost:8000/api/v1/bukus?tersedia=1" -H "Accept: application/json"

# Pengurutan berdasarkan judul ascending
curl.exe -i -X GET "http://localhost:8000/api/v1/bukus?sort=judul&direction=asc" -H "Accept: application/json"
```

### 5.2 GET Detail Buku & Verifikasi HATEOAS Dinamis
```powershell
curl.exe -i -X GET http://localhost:8000/api/v1/bukus/1 -H "Accept: application/json"
```
Perhatikan bagian `_links`:
- Bila stok > 0: menampilkan tautan `self`, `referensi_eksternal`, dan `pinjam_buku`.
- Bila stok = 0: tautan `pinjam_buku` otomatis tidak ditampilkan.

### 5.3 POST Tambah Buku Baru (Terproteksi Bearer Token)
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/bukus `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d @"
{
    "isbn": "978-623-01-9988-1",
    "judul": "Rekayasa Perangkat Lunak Terdistribusi",
    "penulis": "Dosen Informatika Del",
    "penerbit": "Del Press",
    "tahun_terbit": 2024,
    "kategori_id": 1,
    "stok": 5,
    "sinopsis": "Prinsip arsitektur sistem berskala besar."
}
"@
```
Respons: HTTP 201 Created dengan header `Location: http://localhost:8000/api/v1/bukus/{id}`.

### 5.4 PUT vs PATCH Buku
```powershell
# 1. PUT (Penggantian Penuh - Wajib Seluruh Kolom)
curl.exe -i -X PUT http://localhost:8000/api/v1/bukus/1 `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d @"
{
    "isbn": "978-602-0001-01-1",
    "judul": "Pemrograman Web Modern Revisi Penuh",
    "penulis": "Penulis Del Senior",
    "penerbit": "Del Press",
    "tahun_terbit": 2024,
    "kategori_id": 1,
    "stok": 10,
    "sinopsis": "Edisi revisi komprehensif."
}
"@

# 2. PATCH (Pembaruan Parsial - Hanya Kolom yang Diubah)
curl.exe -i -X PATCH http://localhost:8000/api/v1/bukus/1 `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d "{\"stok\": 15}"
```

### 5.5 DELETE Buku (204 No Content vs 409 Conflict)
```powershell
# Menghapus buku tanpa pinjaman aktif (204 No Content)
curl.exe -i -X DELETE http://localhost:8000/api/v1/bukus/2 `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```
Jika buku masih memiliki transaksi pinjaman aktif (`status=dipinjam`), sistem mengembalikan:
`409 Conflict` dengan format RFC 7807 Problem Details.

---

## 6. PENGUJIAN TRANSAKSI SIRKULASI PEMINJAMAN (ACID)

### 6.1 GET Riwayat Peminjaman
```powershell
curl.exe -i -X GET "http://localhost:8000/api/v1/peminjamans?status=dipinjam" `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```

### 6.2 POST Peminjaman Buku (Transaksi Atomik & lockForUpdate)
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/peminjamans `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d @"
{
    "buku_id": 1,
    "nim_peminjam": "12S24012",
    "nama_peminjam": "Bona Siregar",
    "durasi_hari": 7
}
"@
```
Respons: HTTP 201 Created, stok buku berkurang tepat 1 secara atomik.

### 6.3 Pengujian Kasus Batas: Peminjaman Duplikat (409 Conflict)
Kirimkan permintaan yang sama sekali lagi untuk NIM dan buku yang sama:
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/peminjamans `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d @"
{
    "buku_id": 1,
    "nim_peminjam": "12S24012",
    "nama_peminjam": "Bona Siregar",
    "durasi_hari": 7
}
"@
```
Respons: HTTP 409 Conflict RFC 7807: *"Mahasiswa dengan NIM 12S24012 masih meminjam buku ini dan belum mengembalikannya."*

### 6.4 PATCH Pengembalian Buku
```powershell
$LOAN_ID = 1
curl.exe -i -X PATCH http://localhost:8000/api/v1/peminjamans/$LOAN_ID/kembalikan `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```
Respons: HTTP 200 OK, status diubah menjadi `dikembalikan`, kuota stok buku bertambah 1.

### 6.5 Pengujian Idempotensi Pengembalian (409 Conflict)
Kirimkan permintaan pengembalian kembali pada transaksi yang telah dikembalikan:
```powershell
curl.exe -i -X PATCH http://localhost:8000/api/v1/peminjamans/$LOAN_ID/kembalikan `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```
Respons: HTTP 409 Conflict RFC 7807: *"Transaksi peminjaman ini sudah pernah dikembalikan sebelumnya."* Kuota stok terlindungi dari penambahan ganda.

---

## 7. PENGUJIAN REST CONSUMER (OPENLIBRARY)

### 7.1 Sinkronisasi Metadata Eksternal Berdasarkan ISBN
```powershell
curl.exe -i -X GET http://localhost:8000/api/v1/bukus/1/referensi-eksternal `
  -H "Accept: application/json"
```
Endpoint akan melakukan request HTTP timeout 8 detik ke OpenLibrary (`https://openlibrary.org/isbn/{isbn}.json`) dan menyajikan metadata terstruktur (jumlah halaman, penerbit, url cover) secara resilient tanpa mengganggu katalog internal.

---

## 8. MENJALANKAN AUTOMATED FEATURE TESTS

Seluruh skenario pengujian unit dan fungsional dapat dijalankan secara terisolasi menggunakan SQLite In-Memory tanpa mengubah data lokal:

```powershell
# Menjalankan seluruh test suite
php artisan test

# Menjalankan pengujian per modul
php artisan test tests/Feature/Soap/LibrarySoapTest.php
php artisan test tests/Feature/Api/ApiCoreTest.php
php artisan test tests/Feature/Api/AuthApiTest.php
php artisan test tests/Feature/Api/BukuApiTest.php
php artisan test tests/Feature/Api/PeminjamanApiTest.php
php artisan test tests/Feature/Api/OpenLibraryConsumerTest.php

# Memeriksa standar kode dengan Laravel Pint
vendor\bin\pint --test
```
