# Fix Foto Siswa

Perbaikan ini mengatasi error `Unknown column 'foto' in 'field list'` saat menambahkan/mengganti foto siswa.

## Setelah mengganti project

Masuk ke folder `src`, lalu jalankan:

```bash
php artisan migrate
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

Migration `2026_08_19_000002_add_foto_to_siswas_table.php` sengaja dibuat setelah migration refactor sebelumnya, karena migration refactor tersebut menghapus kolom `foto`.

Fitur foto:
- Upload JPG/JPEG/PNG/WEBP maksimal 2 MB.
- Foto disimpan di `storage/app/public/uploads/siswa`.
- Saat mengganti foto, foto lama dihapus.
- Saat menghapus siswa, foto siswa ikut dihapus.
