# Perbaikan QR Siswa

Perubahan pada fitur QR siswa:

- QR menggunakan bentuk kotak (QR Code), bukan barcode garis.
- Tombol `QR PDF Semua` mengunduh seluruh QR siswa yang memiliki NIS sebagai satu file PDF.
- Tombol `PDF Semua` juga tersedia pada setiap baris siswa sebagai shortcut ke PDF gabungan.
- Tombol `QR PNG Semua` mengambil QR satu per satu sebagai PNG terpisah dan memulai download otomatis berurutan.
- Download PNG menggunakan `fetch()` + Blob + nama file `qr-NIS.png`, sehingga tidak lagi mengandalkan navigasi langsung ke URL PNG yang sebelumnya dapat tersimpan sebagai `png.htm` ketika server mengembalikan halaman HTML/error.
- PDF menggunakan PNG base64 di dalam DomPDF agar rendering QR lebih konsisten.

Setelah menyalin project, jalankan:

```bash
composer install
php artisan migrate --force
npm install
npm run build
```
