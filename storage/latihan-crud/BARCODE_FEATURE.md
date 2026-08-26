# Fitur Barcode Siswa

Ditambahkan generator barcode Code 128 untuk setiap siswa.

- Isi barcode **hanya NIS**.
- PNG: barcode murni dalam format PNG.
- PDF: kartu barcode berisi barcode + nama/kelas/NIS sebagai teks di luar barcode.
- Validasi tetap menggunakan endpoint absensi yang menerima NIS, sehingga hasil scan dapat dicocokkan ke tabel `siswas.nis`.

## Dependency baru

- `picqer/php-barcode-generator`
- `barryvdh/laravel-dompdf`

Setelah mengganti project, jalankan:

```bash
composer update picqer/php-barcode-generator barryvdh/laravel-dompdf --with-all-dependencies
php artisan optimize:clear
```

Kemudian di halaman **Data Siswa**, tersedia tombol **PNG** dan **PDF** pada setiap siswa.
