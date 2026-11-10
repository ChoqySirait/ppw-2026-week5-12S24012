# PANDUAN PENGUJIAN REST API & SOAP DENGAN CLI (TERMINAL & POWERSHELL)
## SIPUS-Del — Laravel 11 Web API v1 (Institut Teknologi Del)

Dokumen ini menyediakan panduan komprehensif untuk menjalankan, memverifikasi rute, dan menguji seluruh endpoint REST API dan SOAP SIPUS-Del menggunakan antarmuka baris perintah (CLI) pada Windows PowerShell menggunakan `curl.exe`.

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
*Catatan Data Seeder Awal:*
- Akun Pustakawan: `pustakawan@del.ac.id` / `password123`
- Buku ID 1: Stok = 0 (*Arsitektur Komputasi Cloud Skala Enterprise*)
- Buku ID 2: Stok = 1 (*Struktur Data dan Algoritma Lanjut dengan Go*)
- Buku ID 3: Stok = 5 (memiliki transaksi pinjaman aktif mahasiswa NIM `12S24001`)
- Buku ID 4: Stok = 7 (memiliki riwayat pengembalian)
- Buku ID 5: Stok = 3
- Kategori ID 1 sampai 5 (`KAT-RPL`, `KAT-SI`, `KAT-DS`, `KAT-JK`, `KAT-TI`)

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
*Ekspektasi:* HTTP 200 OK, `Content-Type: text/xml`, memuat definisi `CheckClearanceStatus`.

### 3.2 SOAP Request: Pengecekan Bebas Pustaka (NIM Tanpa Pinjaman Aktif)
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
*Ekspektasi:* HTTP 200 OK, `<tns:active_loans>0</tns:active_loans>`, `<tns:is_cleared>true</tns:is_cleared>`.

### 3.3 SOAP Request: Pengecekan Bebas Pustaka (NIM Memiliki Pinjaman Aktif)
```powershell
curl.exe -i -X POST http://localhost:8000/soap/sipus `
  -H "Content-Type: text/xml; charset=utf-8" `
  -d @"
<?xml version=""1.0"" encoding=""UTF-8""?>
<soapenv:Envelope xmlns:soapenv=""http://schemas.xmlsoap.org/soap/envelope/"" xmlns:sip=""http://sipus.del.ac.id/soap"">
    <soapenv:Header/>
    <soapenv:Body>
        <sip:CheckClearanceStatusRequest>
            <sip:nim_peminjam>12S24001</sip:nim_peminjam>
        </sip:CheckClearanceStatusRequest>
    </soapenv:Body>
</soapenv:Envelope>
"@
```
*Ekspektasi:* HTTP 200 OK, `<tns:active_loans>1</tns:active_loans>`, `<tns:is_cleared>false</tns:is_cleared>`.

### 3.4 SOAP Fault: Permintaan dengan XML Malformed
```powershell
curl.exe -i -X POST http://localhost:8000/soap/sipus `
  -H "Content-Type: text/xml; charset=utf-8" `
  -d "<soapenv:Envelope><soapenv:Body><tagTidakTertutup></soapenv:Body>"
```
*Ekspektasi:* HTTP 400 Bad Request, struktur `<soapenv:Fault>`.

---

## 4. PENGUJIAN AUTENTIKASI REST API (SANCTUM)

### 4.1 Login Pustakawan & Mendapatkan Bearer Token
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/auth/login `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d "{\"email\":\"pustakawan@del.ac.id\",\"password\":\"password123\"}"
```
*Ekspektasi:* HTTP 200 OK, `token_type: "Bearer"`, dan plainTextToken.
Simpan token ke variabel PowerShell:
```powershell
$TOKEN = "PASTE_TOKEN_ANDA_DI_SINI"
```

### 4.2 Login Gagal (Kata Sandi Salah — 401 RFC 7807)
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/auth/login `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d "{\"email\":\"pustakawan@del.ac.id\",\"password\":\"password-salah\"}"
```
*Ekspektasi:* HTTP 401 Unauthorized, `Content-Type: application/problem+json`, title "Unauthorized".

### 4.3 Mengambil Profil Pustakawan Aktif (/me)
```powershell
curl.exe -i -X GET http://localhost:8000/api/v1/auth/me `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```
*Ekspektasi:* HTTP 200 OK, identitas pustakawan tanpa password.

### 4.4 Mengakses Endpoint Terproteksi Tanpa Token (401 RFC 7807)
```powershell
curl.exe -i -X GET http://localhost:8000/api/v1/auth/me `
  -H "Accept: application/json"
```
*Ekspektasi:* HTTP 401 Unauthorized RFC 7807 Problem Details.

---

## 5. PENGUJIAN REST API KATALOG BUKU & HATEOAS

### 5.1 GET Katalog Terpaginasi, Pencarian, & Filter
```powershell
# Katalog lengkap terpaginasi (publik)
curl.exe -i -X GET "http://localhost:8000/api/v1/bukus?per_page=5" -H "Accept: application/json"

# Pencarian judul / penulis
curl.exe -i -X GET "http://localhost:8000/api/v1/bukus?search=Algoritma" -H "Accept: application/json"

# Filter hanya buku yang memiliki stok (tersedia=1)
curl.exe -i -X GET "http://localhost:8000/api/v1/bukus?tersedia=1" -H "Accept: application/json"

# Pengurutan whitelist berdasarkan judul ascending
curl.exe -i -X GET "http://localhost:8000/api/v1/bukus?sort=judul&direction=asc" -H "Accept: application/json"
```

### 5.2 GET Detail Buku & Verifikasi HATEOAS Dinamis
```powershell
curl.exe -i -X GET http://localhost:8000/api/v1/bukus/2 -H "Accept: application/json"
```
*Ekspektasi:* HTTP 200 OK. Memuat atribut `_links`:
- Bila stok > 0: menampilkan tautan `self`, `referensi_eksternal`, dan `pinjam_buku`.
- Pada buku ID 1 (stok = 0): tautan `pinjam_buku` otomatis tidak ditampilkan.

### 5.3 POST Tambah Buku Baru (Terproteksi Bearer Token)
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/bukus `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d @"
{
    "isbn": "978-623-01-CLI-01",
    "judul": "Rekayasa Perangkat Lunak Terdistribusi Modern",
    "penulis": "Dosen Informatika Del",
    "penerbit": "Del Press",
    "tahun_terbit": 2024,
    "kategori_id": 1,
    "stok": 5,
    "sinopsis": "Prinsip arsitektur sistem berskala besar dan toleransi kesalahan."
}
"@
```
*Ekspektasi:* HTTP 201 Created dengan header `Location: http://localhost:8000/api/v1/bukus/{id}`.
Simpan ID buku baru ke variabel (misal `$NEW_BOOK_ID = 6`).

### 5.4 PUT vs PATCH Buku

#### PUT: Penggantian Penuh (Wajib Mengirimkan Seluruh Atribut)
```powershell
curl.exe -i -X PUT "http://localhost:8000/api/v1/bukus/$NEW_BOOK_ID" `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d @"
{
    "isbn": "978-623-01-CLI-PUT",
    "judul": "Rekayasa Perangkat Lunak Terdistribusi (Edisi Lengkap)",
    "penulis": "Prof. Dosen Informatika Del",
    "penerbit": "Del Press International",
    "tahun_terbit": 2024,
    "kategori_id": 1,
    "stok": 10,
    "sinopsis": "Edisi revisi penuh komprehensif."
}
"@
```
*Ekspektasi:* HTTP 200 OK, seluruh atribut digantikan.

#### PATCH: Pembaruan Parsial (Hanya Mengirimkan Field yang Diubah)
```powershell
curl.exe -i -X PATCH "http://localhost:8000/api/v1/bukus/$NEW_BOOK_ID" `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d "{\"stok\": 15}"
```
*Ekspektasi:* HTTP 200 OK, hanya stok yang diperbarui, field lain tidak berubah.

### 5.5 DELETE Buku: 204 No Content vs 409 Conflict

#### DELETE Sukses: Buku Tanpa Pinjaman Aktif (204 No Content)
Hapus buku uji baru yang dibuat di langkah 5.3:
```powershell
curl.exe -i -X DELETE "http://localhost:8000/api/v1/bukus/$NEW_BOOK_ID" `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```
*Ekspektasi:* HTTP 204 No Content tanpa tubuh respons.

#### DELETE Ditolak: Buku Memiliki Pinjaman Aktif (409 Conflict)
Hapus buku ID 3 hasil seeder yang sedang berstatus dipinjam:
```powershell
curl.exe -i -X DELETE "http://localhost:8000/api/v1/bukus/3" `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```
*Ekspektasi:* HTTP 409 Conflict RFC 7807: *"Buku tidak dapat dihapus karena masih ada transaksi peminjaman aktif."*

---

## 6. PENGUJIAN TRANSAKSI SIRKULASI PEMINJAMAN (ACID CONCURRENCY)

### 6.1 GET Riwayat Peminjaman Aktif
```powershell
curl.exe -i -X GET "http://localhost:8000/api/v1/peminjamans?status=dipinjam" `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```

### 6.2 POST Peminjaman Sukses (Buku ID 2, Stok Awal = 1)
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/peminjamans `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d @"
{
    "buku_id": 2,
    "nim_peminjam": "12S24012",
    "nama_peminjam": "Choqy Pananda Sirait",
    "durasi_hari": 7
}
"@
```
*Ekspektasi:* HTTP 201 Created. Stok buku ID 2 berkurang dari 1 menjadi 0 secara atomik. Simpan ID transaksi baru ke variabel (misal `$LOAN_ID = 3`).

### 6.3 POST Peminjaman Gagal: Stok Habis (Buku ID 1, Stok = 0)
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/peminjamans `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d @"
{
    "buku_id": 1,
    "nim_peminjam": "12S24012",
    "nama_peminjam": "Choqy Pananda Sirait",
    "durasi_hari": 7
}
"@
```
*Ekspektasi:* HTTP 422 Unprocessable Content RFC 7807: *"Stok buku telah habis. Peminjaman dibatalkan."*

### 6.4 POST Peminjaman Gagal: Pinjaman Duplikat (Buku ID 3 oleh NIM 12S24001)
Kirimkan permintaan peminjaman untuk buku ID 3 yang masih aktif dipinjam oleh mahasiswa NIM 12S24001 hasil seeder (buku ID 3 memiliki sisa stok 5 > 0, sehingga lolos validasi stok dan mengevaluasi aturan pinjaman ganda):
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/peminjamans `
  -H "Authorization: Bearer $TOKEN" `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d @"
{
    "buku_id": 3,
    "nim_peminjam": "12S24001",
    "nama_peminjam": "Lamhot Simanjuntak",
    "durasi_hari": 7
}
"@
```
*Ekspektasi:* HTTP 409 Conflict RFC 7807: *"Mahasiswa dengan NIM 12S24001 masih meminjam buku ini dan belum mengembalikannya."*

> [!NOTE]
> **Catatan Concurrency Locking (MySQL 8.0 InnoDB vs SQLite)**:
> Pada demonstrasi konkurensi nyata, sistem wajib dijalankan di atas mesin MySQL 8.0 dengan storage engine InnoDB (misalnya pada Laragon lokal). Klausul `lockForUpdate()` mengeksekusi `SELECT ... FOR UPDATE` pada tingkat baris (*Row-Level Pessimistic Lock*) InnoDB sehingga transaksi kedua akan diblokir (*blocking wait*) sampai transaksi pertama selesai melakukan `COMMIT` atau `ROLLBACK`. Pengujian otomatis PHPUnit menggunakan SQLite In-Memory untuk kecepatan pengujian fungsional terisolasi, namun verifikasi konkurensi fisik mutlak membutuhkan InnoDB.

### 6.5 PATCH Pengembalian Buku Sukses
```powershell
curl.exe -i -X PATCH "http://localhost:8000/api/v1/peminjamans/$LOAN_ID/kembalikan" `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```
*Ekspektasi:* HTTP 200 OK, status diubah menjadi `dikembalikan`, `tanggal_kembali` terisi, dan kuota stok buku ID 2 pulih kembali menjadi 1.

### 6.6 PATCH Pengembalian Duplikat (Idempotensi Guard)
Kirimkan permintaan pengembalian sekali lagi pada transaksi yang sama:
```powershell
curl.exe -i -X PATCH "http://localhost:8000/api/v1/peminjamans/$LOAN_ID/kembalikan" `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```
*Ekspektasi:* HTTP 409 Conflict RFC 7807: *"Transaksi peminjaman ini sudah pernah dikembalikan sebelumnya."* Kuota stok terlindungi dari penambahan ganda.

---

## 7. PENGUJIAN REST SERVICE CONSUMER (OPENLIBRARY)

### 7.1 Sinkronisasi Metadata Eksternal (reference_book_id)
Gunakan buku seeder terpisah (misalnya ID 2) yang tidak dihapus saat pengujian CRUD:
```powershell
$REFERENCE_BOOK_ID = 2
curl.exe -i -X GET "http://localhost:8000/api/v1/bukus/$REFERENCE_BOOK_ID/referensi-eksternal" `
  -H "Accept: application/json"
```
*Ekspektasi:* HTTP 200 OK. Menyajikan metadata hasil sinkronisasi OpenLibrary (`jumlah_halaman`, `penerbit`, dll.) secara aman dengan batas timeout 8 detik, atau informasi `fallback_lokal` saat metadata ISBN tidak ditemukan pada OpenLibrary.

---

## 8. PENGUJIAN LOGOUT (PENCABUTAN TOKEN)

### 8.1 POST Logout (Revoke Token)
Jalankan logout di bagian akhir agar token dicabut setelah seluruh skenario pengujian selesai:
```powershell
curl.exe -i -X POST http://localhost:8000/api/v1/auth/logout `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```
*Ekspektasi:* HTTP 200 OK, `"message": "Token berhasil dicabut. Logout berhasil."`.

### 8.2 Verifikasi Token Tidak Lagi Berlaku (401 RFC 7807)
```powershell
curl.exe -i -X GET http://localhost:8000/api/v1/auth/me `
  -H "Authorization: Bearer $TOKEN" `
  -H "Accept: application/json"
```
*Ekspektasi:* HTTP 401 Unauthorized RFC 7807.

---

## 9. MENJALANKAN AUTOMATED FEATURE TESTS

Seluruh skenario pengujian unit dan fungsional dapat dijalankan secara terisolasi menggunakan SQLite In-Memory tanpa mengubah data lokal:

```powershell
# Menjalankan seluruh test suite (45 tests, 232 assertions)
php artisan test

# Menjalankan pengujian per modul
php artisan test tests/Feature/Soap/LibrarySoapTest.php
php artisan test tests/Feature/Api/ApiCoreTest.php
php artisan test tests/Feature/Api/AuthApiTest.php
php artisan test tests/Feature/Api/BukuApiTest.php
php artisan test tests/Feature/Api/PeminjamanApiTest.php
php artisan test tests/Feature/Api/OpenLibraryConsumerTest.php

# Memeriksa standar format kode dengan Laravel Pint
vendor\bin\pint --test
```
