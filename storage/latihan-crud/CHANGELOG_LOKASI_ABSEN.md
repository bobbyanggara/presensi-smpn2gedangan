# Perubahan: Absen per Lokasi (Perpustakaan / Masjid)

Sebelumnya fitur ini cuma kerangka (model `AttendanceLocation` & `AttendanceSession` ada,
tapi tabel `attendance_locations` belum pernah dimigrasi dan tidak ada route yang mengarah
ke controllernya). Sekarang bagian minimalnya sudah nyambung:

## Yang ditambah/diubah
1. Migration baru: `attendance_locations` table (belum ada sebelumnya).
2. Migration baru: kolom `location_id` (nullable) di tabel `absensis`.
3. Seeder `AttendanceLocationSeeder`: isi "Perpustakaan" & "Masjid", dipanggil dari `DatabaseSeeder`.
4. `Absensi` model: tambah relasi `location()`.
5. `AbsensiController::scan()`: kirim daftar lokasi ke view + ingat lokasi terakhir dipilih (session).
6. `AbsensiController::proses()`: terima & simpan `location_id`.
7. View `scan.blade.php`: tambah dropdown pilih lokasi.
8. View `rekap.blade.php` & `AbsensiExport`: tambah kolom Lokasi.

## Catatan penting
- Aturan "1 siswa cuma bisa absen 1x per hari" TETAP berlaku global (unique per siswa+tanggal),
  bukan per lokasi. Lokasi cuma dicatat sebagai info titik scan, sesuai konfirmasi kebutuhan sekolah.
- `AttendanceSessionController` dan `AttendanceSessionFinalizer` dibiarkan ada tapi TIDAK dipakai
  (tidak terdaftar di routes). Itu untuk fitur lanjutan "sesi absen otomatis tandai alpha" yang
  belum dibutuhkan sekarang — aman diabaikan atau dihapus nanti.

## Setelah replace project
```bash
composer install
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\AttendanceLocationSeeder
php artisan optimize:clear
npm run build
```

---

## Update: absen dipisah per lokasi (bukan gabung 1x/hari lagi)

Ternyata kebutuhan sebenarnya: siswa harus BISA absen di Perpustakaan DAN Masjid
di hari yang sama, dicatat sebagai 2 baris terpisah — bukan cuma 1 absen umum/hari.

### Perubahan
1. Migration baru: ganti unique constraint dari `(siswa_id, tanggal)` jadi
   `(siswa_id, tanggal, location_id)` — jadi 1 siswa bisa punya 1 absen per lokasi per hari.
2. `AbsensiController::proses()`: sekarang **wajib** pilih lokasi (validasi `required`),
   dan cek "sudah absen" difilter per lokasi juga (bukan global lagi).
3. Dashboard (`AbsensiController::dashboard()`): dihitung ulang biar nggak dobel —
   1 siswa yang absen di 2 lokasi tetap dihitung 1x di "sudah absen hari ini"
   (pakai scan pertamanya buat status hadir/telat).
4. View scan: dropdown lokasi sekarang wajib dipilih, nggak bisa disubmit kalau kosong.

### Setelah replace project
```bash
php artisan migrate
php artisan view:clear
```

---

## Update: filter lokasi di Rekap Harian & Laporan Bulanan

- `/absensi/rekap` — dropdown "Semua lokasi" / pilih lokasi tertentu.
- `/absensi/laporan` — dropdown lokasi ditambahkan di samping filter bulan/tahun.
- Export Excel ikut lokasi yang lagi difilter (kalau "Semua lokasi", export semua seperti biasa).

---

## Update: tombol PDF Semua sekarang ada status loading (kayak PNG Semua)

Backend `barcodePdfAll` sebenarnya udah dari awal menggabungkan semua QR
(sesuai filter search/kelas yang aktif) jadi 1 file PDF — itu nggak diubah.

Yang diubah cuma tombolnya:
- Sebelumnya: `<a href download>` biasa, klik lalu diam aja sampai file muncul
  (buat 1160 siswa proses generate PDF-nya berat, jadi kelihatan kayak macet).
- Sekarang: tombol berubah jadi "Menyiapkan PDF..." selagi server generate,
  lalu baru download otomatis begitu selesai — sama seperti pola tombol PNG Semua.
- Kalau gagal (misal timeout/error server), muncul alert dengan pesan error,
  bukan halaman putih/redirect diam-diam.

---

## Update: debug "PDF Semua" gagal padahal cuma sedikit siswa

Dua perbaikan sekaligus karena belum ada log error yang bisa dicek:

1. **Pesan error sekarang lebih detail saat `APP_DEBUG=true`** (mode lokal/dev) —
   pesan alert bakal nampilin nama exception, pesan asli, file, dan baris kode yang
   error. Di production (`APP_DEBUG=false`) pesannya tetap generik seperti biasa
   (aman, tidak bocorin detail internal ke user).
2. **QR generation per siswa dibungkus try/catch sendiri-sendiri** — kalau ada 1 NIS
   yang bikin gagal generate QR, pesan errornya sekarang nyebutin NIS mana yang
   bermasalah, bukan cuma "gagal" tanpa info.
3. **Deteksi sukses/gagal di JS diperlonggar** — sebelumnya kode JS mengharuskan
   header `content-type` PERSIS mengandung `application/pdf`, kalau server balas
   sedikit beda (misal ada `; charset=...` tambahan atau variasi header lain),
   JS-nya salah mengira response gagal padahal PDF-nya sendiri sebenarnya valid.
   Sekarang cuma dicek: kalau responsnya BUKAN html error page dan status-nya ok,
   dianggap berhasil.

Kalau setelah update ini masih gagal, pesan alert-nya sekarang harusnya udah
nunjukin baris kode & exception aslinya — tinggal share pesan itu.
