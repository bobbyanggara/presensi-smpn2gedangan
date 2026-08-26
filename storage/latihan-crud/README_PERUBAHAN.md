# Perubahan Sistem Data Siswa & Import Massal

Project ini sudah disesuaikan agar data siswa menggunakan **NIS, Nama, dan Kelas** saja. RFID tidak lagi digunakan sebagai identitas siswa.

## Struktur data siswa

Tabel `siswas` pada hasil migrasi akhir memiliki:

- `id`
- `nis` — unik, maksimal 30 karakter
- `nama` — maksimal 150 karakter
- `kelas` — maksimal 50 karakter
- `created_at`
- `updated_at`

Kolom lama `rfid_uid`, `jurusan`, `no_hp_ortu`, dan `foto` dihapus oleh migration refactor.

> Migration menambahkan `nis` sebagai nullable terlebih dahulu supaya database lama tidak dipaksa menebak NIS yang benar. Data siswa lama yang belum memiliki NIS harus diisi melalui import Excel sebelum dianggap lengkap oleh aplikasi.

## Import Excel massal

Format file:

| NIS | Nama | Kelas |
|---|---|---|
| 240001 | Ahmad Rizky | IX A |
| 240002 | Budi Santoso | IX A |
| 240003 | Citra Lestari | IX B |

Fitur import:

1. Validasi NIS, Nama, dan Kelas.
2. Deteksi NIS duplikat di dalam file.
3. Pengecekan NIS yang sudah ada di database.
4. Preview sebelum penyimpanan.
5. Label `BARU` atau `UPDATE`.
6. Bulk upsert dalam chunk 500 baris.
7. Semua penyimpanan dibungkus database transaction.
8. Maksimal 5.000 baris per file.
9. Preview disimpan di disk lokal dengan token acak, bukan seluruh isi Excel di session/cookie.

Untuk NIS dengan angka nol di depan, format kolom NIS sebagai **Text** di Excel.

## Menu / route

- `GET /siswa` — daftar siswa, search, filter kelas, pagination 50.
- `GET /siswa/import` — halaman import.
- `POST /siswa/import/preview` — validasi dan membuat preview.
- `POST /siswa/import/confirm` — menyimpan hasil preview.
- `GET /siswa/import/template` — download template Excel.

## Absensi

Absensi sekarang mencari siswa dengan `nis`, bukan `rfid_uid`.

Input scan dapat berupa:

- NIS dari scanner USB yang bertindak sebagai keyboard.
- Barcode/QR yang berisi NIS melalui kamera.
- NIS yang diketik manual.

Tabel `absensis` memiliki unique constraint `(siswa_id, tanggal)` untuk mencegah satu siswa tercatat dua kali pada hari yang sama, termasuk saat dua request datang hampir bersamaan.

## Menjalankan project

Setelah dependency tersedia:

```bash
composer install
php artisan migrate
php artisan storage:link
php artisan serve
```

Jika menggunakan frontend Vite:

```bash
npm install
npm run build
```

## Pemeriksaan yang sudah dilakukan

- Semua file PHP pada `app`, `database`, `routes`, `config`, `bootstrap`, dan `tests` lolos `php -l`.
- Tidak ada referensi runtime `rfid_uid`, `no_hp_ortu`, `jurusan`, atau `foto` pada `app`, `routes`, dan `resources/views` utama.
- `composer.json` project menggunakan Laravel 12 dan `maatwebsite/excel`.

Runtime Laravel penuh belum dapat dijalankan di lingkungan pemeriksaan ini karena binary Composer dan folder `vendor` tidak tersedia. Setelah `composer install` dilakukan di komputer project, jalankan migration dan smoke test sebelum memasukkan data produksi.
