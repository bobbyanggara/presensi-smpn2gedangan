<?php

namespace App\Http\Controllers;

use App\Exports\SiswaDataExport;
use App\Exports\SiswaTemplateExport;
use App\Imports\SiswaImport;
use App\Models\Siswa;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SiswaController extends Controller
{
    private const IMPORT_SESSION_KEY = 'siswa_import_token';
    private const IMPORT_DISK = 'local';

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $kelas = trim((string) $request->input('kelas', ''));

        $filtered = $this->filteredSiswaQuery($request);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 50;
        $siswas = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $kelasOptions = Siswa::query()
            ->whereNotNull('kelas')
            ->where('kelas', '<>', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');

        // Jumlah siswa per kelas, dipakai di panel "Hapus per Kelas" supaya guru
        // tahu berapa siswa yang akan ikut terhapus sebelum menekan konfirmasi.
        $kelasCounts = Siswa::query()
            ->whereNotNull('kelas')
            ->where('kelas', '<>', '')
            ->groupBy('kelas')
            ->orderBy('kelas')
            ->selectRaw('kelas, count(*) as total')
            ->pluck('total', 'kelas');

        return view('siswa.index', compact('siswas', 'search', 'kelas', 'kelasOptions', 'kelasCounts'));
    }

    public function create()
    {
        return view('siswa.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nis' => ['required', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:150'],
            'kelas' => ['required', 'string', 'max:50'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->ensureNisAvailable($data['nis']);

        if ($request->hasFile('foto') && Schema::hasColumn('siswas', 'foto')) {
            $data['foto'] = $request->file('foto')->store('uploads/siswa', 'public');
        }

        Siswa::create($data);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function show(Siswa $siswa)
    {
        return view('siswa.show', compact('siswa'));
    }

    public function edit(Siswa $siswa)
    {
        return view('siswa.edit', compact('siswa'));
    }

    public function foto(Siswa $siswa)
    {
        if (!$siswa->foto || !Storage::disk('public')->exists($siswa->foto)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($siswa->foto), [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function update(Request $request, Siswa $siswa)
    {
        $data = $request->validate([
            'nis' => ['required', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:150'],
            'kelas' => ['required', 'string', 'max:50'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->ensureNisAvailable($data['nis'], $siswa->id);

        // Hanya menyentuh kolom foto jika database memang sudah memiliki kolom tersebut.
        // Migration terbaru akan membuat kolom foto untuk database lama.
        if ($request->hasFile('foto') && Schema::hasColumn('siswas', 'foto')) {
            if ($siswa->foto) {
                Storage::disk('public')->delete($siswa->foto);
            }
            $data['foto'] = $request->file('foto')->store('uploads/siswa', 'public');
        } else {
            unset($data['foto']);
        }

        $siswa->update($data);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        if ($siswa->foto && Storage::disk('public')->exists($siswa->foto)) {
            Storage::disk('public')->delete($siswa->foto);
        }

        $siswa->delete();

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * Hapus semua siswa sekaligus untuk satu atau beberapa kelas (mis. kelas
     * yang sudah lulus), supaya guru tidak perlu hapus satu-satu. Kolom
     * 'kelas' tidak terenkripsi jadi WHERE kelas IN (...) langsung kena index,
     * cepat walau datanya ribuan. Riwayat absensi ikut terhapus otomatis
     * lewat foreign key cascade di tabel absensis.
     */
    public function destroyByKelas(Request $request)
    {
        $data = $request->validate([
            'kelas' => ['required', 'array', 'min:1'],
            'kelas.*' => ['string'],
            'confirm' => ['required', 'in:HAPUS'],
        ], [
            'kelas.required' => 'Pilih minimal satu kelas yang mau dihapus.',
            'confirm.in' => 'Ketik HAPUS persis untuk konfirmasi.',
        ]);

        $kelasList = array_values(array_unique(array_filter(array_map('trim', $data['kelas']))));

        if ($kelasList === []) {
            return redirect()->route('siswa.index')->with('error', 'Pilih minimal satu kelas yang mau dihapus.');
        }

        $count = Siswa::query()->whereIn('kelas', $kelasList)->count();

        if ($count === 0) {
            return redirect()->route('siswa.index')->with('error', 'Tidak ada siswa di kelas yang dipilih.');
        }

        $fotos = Siswa::query()->whereIn('kelas', $kelasList)->pluck('foto')->filter();

        DB::transaction(function () use ($kelasList) {
            Siswa::query()->whereIn('kelas', $kelasList)->delete();
        });

        foreach ($fotos as $foto) {
            if (Storage::disk('public')->exists($foto)) {
                Storage::disk('public')->delete($foto);
            }
        }

        $daftarKelas = implode(', ', $kelasList);

        return redirect()->route('siswa.index')->with('success', "{$count} siswa dari kelas {$daftarKelas} berhasil dihapus.");
    }

    public function importForm(Request $request)
    {
        $preview = null;
        $token = $request->session()->get(self::IMPORT_SESSION_KEY);

        if ($token && preg_match('/^[A-Za-z0-9]+$/', $token)) {
            $path = $this->importPath($token);

            if (Storage::disk(self::IMPORT_DISK)->exists($path)) {
                try {
                    $preview = json_decode(
                        Storage::disk(self::IMPORT_DISK)->get($path),
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );
                } catch (\Throwable $e) {
                    report($e);
                    $request->session()->forget(self::IMPORT_SESSION_KEY);
                    Storage::disk(self::IMPORT_DISK)->delete($path);
                }
            } else {
                $request->session()->forget(self::IMPORT_SESSION_KEY);
            }
        }

        $rows = is_array($preview) ? ($preview['rows'] ?? []) : [];
        $importErrors = is_array($preview) ? ($preview['errors'] ?? []) : [];

        return view('siswa.import', compact('preview', 'rows', 'importErrors'));
    }

    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $import = new SiswaImport();
        Excel::import($import, $request->file('file'));

        $rows = $import->rows;
        $errors = $import->errors;

        if (count($rows) > 5000) {
            $errors[] = 'File melebihi batas 5.000 baris per import.';
        }

        if ($rows === [] && $errors === []) {
            $errors[] = 'File tidak berisi data siswa.';
        }

        $existingByNis = Siswa::query()->get()->keyBy(fn (Siswa $siswa) => (string) $siswa->nis);

        $newCount = 0;
        $updateCount = 0;
        foreach ($rows as &$row) {
            $existing = $existingByNis->get(trim((string) $row['nis']));
            $row['_action'] = $existing ? 'UPDATE' : 'BARU';
            $row['_existing_id'] = $existing?->id;
            $row['_line'] = (int) $row['_line'];
            if ($row['_action'] === 'UPDATE') {
                $updateCount++;
            } else {
                $newCount++;
            }
        }
        unset($row);

        $token = Str::random(64);
        $path = $this->importPath($token);
        Storage::disk(self::IMPORT_DISK)->put($path, json_encode([
            'rows' => array_values($rows),
            'errors' => array_values($errors),
            'created_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR));

        $this->forgetImportPreview();
        $request->session()->put(self::IMPORT_SESSION_KEY, $token);

        return redirect()->route('siswa.import')->with('import_summary', [
            'total' => count($rows),
            'new' => $newCount,
            'update' => $updateCount,
            'errors' => count($errors),
        ]);
    }

    public function importConfirm(Request $request)
    {
        $token = $request->session()->pull(self::IMPORT_SESSION_KEY);

        if (!$token || !preg_match('/^[A-Za-z0-9]+$/', $token)) {
            return redirect()->route('siswa.import')->with('error', 'Preview import sudah kedaluwarsa. Silakan upload ulang.');
        }

        $path = $this->importPath($token);
        if (!Storage::disk(self::IMPORT_DISK)->exists($path)) {
            return redirect()->route('siswa.import')->with('error', 'Data preview tidak ditemukan. Silakan upload ulang.');
        }

        try {
            $payload = json_decode(Storage::disk(self::IMPORT_DISK)->get($path), true, 512, JSON_THROW_ON_ERROR);
            $rows = $payload['rows'] ?? [];
            $errors = $payload['errors'] ?? [];

            // Jika ada masalah, data yang valid tetap boleh diimpor.
            // Baris bermasalah sudah tidak dimasukkan ke $rows oleh SiswaImport,
            // jadi cukup proses $rows yang valid dan lewati $errors.
            if ($rows === []) {
                return redirect()->route('siswa.import')->with('error', 'Tidak ada data valid yang bisa diimpor. Periksa masalah pada file.');
            }

            // Catatan performa: dulu kode ini memanggil Siswa::findByNis() per baris,
            // yang men-decrypt SELURUH tabel siswa untuk tiap baris import (O(N*M)).
            // Untuk 5.000 baris itu bisa puluhan juta operasi decrypt -> timeout 60 detik.
            // _existing_id sudah dihitung sekali di importPreview(), jadi di sini cukup
            // lookup by primary key (indexed, murah) alih-alih scan+decrypt ulang.
            @set_time_limit(300);

            DB::transaction(function () use ($rows) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    $now = now();
                    $ids = array_values(array_filter(array_column($chunk, '_existing_id')));
                    $existingById = $ids === []
                        ? collect()
                        : Siswa::query()->whereIn('id', $ids)->get()->keyBy('id');

                    foreach ($chunk as $row) {
                        $existing = !empty($row['_existing_id'])
                            ? $existingById->get($row['_existing_id'])
                            : null;

                        if ($existing) {
                            $existing->nis = $row['nis'];
                            $existing->nama = $row['nama'];
                            $existing->kelas = $row['kelas'];
                            $existing->save();
                        } else {
                            Siswa::create([
                                'nis' => $row['nis'],
                                'nama' => $row['nama'],
                                'kelas' => $row['kelas'],
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                }
            });

            Storage::disk(self::IMPORT_DISK)->delete($path);

            return redirect()->route('siswa.index')->with('success', 'Import siswa berhasil. ' . count($rows) . ' baris diproses.');
        } catch (\Throwable $e) {
            $request->session()->put(self::IMPORT_SESSION_KEY, $token);
            report($e);

            return redirect()->route('siswa.import')->with('error', 'Import gagal dan tidak ada perubahan yang disimpan. Periksa log aplikasi untuk detail teknis.');
        }
    }

    /**
     * Diproses lewat AJAX oleh JS di halaman import, dipanggil berulang kali
     * dengan offset yang naik supaya frontend bisa tampilkan progress bar
     * ("1.500 / 5.000 siswa") dan supaya satu request tidak perlu memproses
     * semua baris sekaligus (menghindari risiko timeout pada file sangat besar).
     */
    public function importProcessChunk(Request $request)
    {
        $token = $request->session()->get(self::IMPORT_SESSION_KEY);
        if (!$token || !preg_match('/^[A-Za-z0-9]+$/', $token)) {
            return response()->json(['message' => 'Preview import sudah kedaluwarsa. Silakan upload ulang.'], 422);
        }

        $path = $this->importPath($token);
        if (!Storage::disk(self::IMPORT_DISK)->exists($path)) {
            return response()->json(['message' => 'Data preview tidak ditemukan. Silakan upload ulang.'], 422);
        }

        $request->validate([
            'offset' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 250);

        try {
            $payload = json_decode(Storage::disk(self::IMPORT_DISK)->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Data preview rusak. Silakan upload ulang.'], 422);
        }

        $rows = $payload['rows'] ?? [];
        $errors = $payload['errors'] ?? [];

        // Tetap proses data yang valid walaupun ada baris yang bermasalah.
        // $errors hanya menjadi informasi baris yang dilewati.
        if ($rows === []) {
            return response()->json(['message' => 'Tidak ada data valid yang bisa diimpor. Periksa masalah pada file.'], 422);
        }

        $total = count($rows);
        $chunk = array_slice($rows, $offset, $limit);

        // Sudah habis: chunk terakhir sebelumnya adalah baris terakhir.
        if ($chunk === []) {
            Storage::disk(self::IMPORT_DISK)->delete($path);
            $request->session()->forget(self::IMPORT_SESSION_KEY);

            return response()->json(['processed' => $total, 'total' => $total, 'done' => true]);
        }

        try {
            DB::transaction(function () use ($chunk) {
                $now = now();
                $ids = array_values(array_filter(array_column($chunk, '_existing_id')));
                $existingById = $ids === []
                    ? collect()
                    : Siswa::query()->whereIn('id', $ids)->get()->keyBy('id');

                foreach ($chunk as $row) {
                    $existing = !empty($row['_existing_id'])
                        ? $existingById->get($row['_existing_id'])
                        : null;

                    if ($existing) {
                        $existing->nis = $row['nis'];
                        $existing->nama = $row['nama'];
                        $existing->kelas = $row['kelas'];
                        $existing->save();
                    } else {
                        Siswa::create([
                            'nis' => $row['nis'],
                            'nama' => $row['nama'],
                            'kelas' => $row['kelas'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Gagal memproses sebagian data. Tidak ada perubahan pada batch ini yang disimpan.'], 500);
        }

        $processed = min($offset + $limit, $total);
        $done = $processed >= $total;

        if ($done) {
            Storage::disk(self::IMPORT_DISK)->delete($path);
            $request->session()->forget(self::IMPORT_SESSION_KEY);
        }

        return response()->json(['processed' => $processed, 'total' => $total, 'done' => $done]);
    }

    public function downloadTemplate()
    {
        return Excel::download(new SiswaTemplateExport(), 'template-data-siswa.xlsx');
    }

    /**
     * Export data siswa (NIS, nama, kelas) ke Excel — terpisah dari fitur QR Code.
     */
    public function exportData(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $kelas = trim((string) $request->input('kelas', ''));
        $namaFile = 'data-siswa' . ($kelas !== '' ? '-' . Str::slug($kelas) : '') . '.xlsx';

        return Excel::download(new SiswaDataExport($search, $kelas), $namaFile);
    }

    /**
     * Halaman "QR Absensi" — sengaja dipisah dari halaman Data Siswa supaya
     * operator sekolah tidak bingung: Data Siswa untuk kelola data (tambah/
     * edit/hapus/import), halaman ini khusus untuk generate & unduh QR.
     */
    public function exportQr(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $kelas = trim((string) $request->input('kelas', ''));

        $filtered = $this->filteredSiswaQuery($request);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 50;
        $matchedSiswas = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // matchedCount = siswa dalam filter saat ini yang NIS-nya terisi
        // (hanya ini yang benar-benar bisa diunduh QR-nya).
        $matchedCount = $filtered->filter(fn (Siswa $siswa) => filled($siswa->nis))->count();

        // Dihitung dari seluruh data (bukan cuma yang difilter), supaya
        // operator tahu ada berapa siswa yang perlu dilengkapi NIS-nya.
        $totalWithoutNis = Siswa::query()->get()->filter(fn (Siswa $siswa) => blank($siswa->nis))->count();

        $kelasOptions = Siswa::query()
            ->whereNotNull('kelas')
            ->where('kelas', '<>', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');

        return view('siswa.export-qr', compact('search', 'kelas', 'matchedSiswas', 'matchedCount', 'totalWithoutNis', 'kelasOptions'));
    }

    public function barcodePng(Siswa $siswa)
    {
        // Hanya NIS yang di-encode ke QR. Nama & kelas tidak perlu ikut
        // masuk karena saat discan (AbsensiController::proses) sudah
        // di-lookup ulang dari database lewat NIS. Payload yang lebih kecil
        // ini yang memungkinkan QR tetap version 1 (21x21 modul).
        $result = $this->buildQrPng((string) $siswa->nis, 600);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'attachment; filename="qr-' . $siswa->nis . '.png"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * PDF kartu QR untuk satu siswa saja (tombol "PDF" di setiap baris tabel).
     */
    public function barcodePdf(Siswa $siswa)
    {
        $qr = $this->buildQrPng((string) $siswa->nis, 320);

        $items = collect([[
            'siswa' => $siswa,
            'qrDataUri' => 'data:' . $qr->getMimeType() . ';base64,' . base64_encode($qr->getString()),
        ]]);

        return view('siswa.barcode-print', [
            'items' => $items,
            'judul' => 'QR ' . $siswa->nama,
        ]);
    }

    public function barcodePngAll(Request $request)
    {
        $siswas = $this->filteredSiswaQuery($request)
            ->filter(fn (Siswa $siswa) => filled($siswa->nis))
            ->sortBy('nama')->values();

        return response()->json([
            'count' => $siswas->count(),
            'items' => $siswas->map(fn (Siswa $siswa) => [
                'nis' => (string) $siswa->nis,
                'nama' => $siswa->nama,
                'kelas' => $siswa->kelas,
                'url' => route('siswa.barcode.png', $siswa),
            ])->values(),
        ]);
    }

    public function barcodePdfAll(Request $request)
    {
        $siswas = $this->filteredSiswaQuery($request)
            ->filter(fn (Siswa $siswa) => filled($siswa->nis))
            ->sortBy('nama')->values();

        if ($siswas->isEmpty()) {
            return redirect()->route('siswa.index')->with('error', 'Tidak ada siswa dengan NIS yang cocok dengan filter untuk dibuatkan QR.');
        }

        // PDF gabungan bisa berisi banyak QR (gambar base64), jadi longgarkan
        // batas waktu & memori PHP agar tidak berhenti diam-diam di tengah proses.
        // Catatan: ini hanya mengatur batas di sisi PHP. Kalau di server ada
        // reverse proxy (Nginx/Apache) atau PHP-FPM di depannya, timeout di
        // sana juga perlu dinaikkan (lihat catatan di bawah kode ini).
        @set_time_limit(600);
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '1024M');
        }

        try {
            $items = $siswas->map(function (Siswa $siswa) {
                try {
                    // Generate langsung di memori (tanpa cache ke disk) supaya
                    // tidak tergantung folder storage bisa ditulis atau tidak.
                    // Hanya NIS yang di-encode (lihat catatan di barcodePng()).
                    $qr = $this->buildQrPng((string) $siswa->nis, 320);
                } catch (\Throwable $e) {
                    throw new \RuntimeException('Gagal generate QR untuk NIS ' . $siswa->nis . ': ' . $e->getMessage(), 0, $e);
                }

                return [
                    'siswa' => $siswa,
                    'qrDataUri' => 'data:' . $qr->getMimeType() . ';base64,' . base64_encode($qr->getString()),
                ];
            });

            return view('siswa.barcode-print', [
                'items' => $items,
                'judul' => 'QR Siswa',
            ]);
        } catch (\Throwable $e) {
            report($e);

            $message = 'Gagal menyiapkan QR gabungan. Coba filter kelas/nama agar jumlah siswa lebih sedikit, lalu coba lagi.';
            $message .= ' [DEBUG] ' . get_class($e) . ': ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')';

            return redirect()->route('siswa.index')->with('error', $message);
        }
    }

    private function ensureNisAvailable(string $nis, ?int $ignoreId = null): void
    {
        $nis = trim($nis);
        if ($nis === '') {
            return;
        }

        $existing = Siswa::query()->get()->first(function (Siswa $item) use ($nis, $ignoreId) {
            return ($ignoreId === null || $item->id !== $ignoreId) && (string) $item->nis === $nis;
        });

        if ($existing) {
            throw ValidationException::withMessages([
                'nis' => 'NIS tersebut sudah terdaftar.',
            ]);
        }
    }

    /**
     * Query siswa yang sudah difilter sesuai parameter "search" dan "kelas"
     * dari request — dipakai bersama oleh halaman index dan download massal
     * (PNG Semua / PDF Semua) agar hasil unduhan mengikuti filter yang aktif.
     */
    private function filteredSiswaQuery(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $kelas = trim((string) $request->input('kelas', ''));

        // Karena NIS terenkripsi tidak bisa dicari dengan SQL LIKE, filtering
        // NIS dilakukan setelah data didecrypt oleh model. Dengan ±1.500 siswa
        // jumlah ini masih wajar untuk aplikasi absensi.
        $items = Siswa::query()
            ->when($kelas !== '', fn ($query) => $query->where('kelas', $kelas))
            ->orderBy('nama')
            ->get();

        if ($search === '') {
            return $items;
        }

        $searchLower = mb_strtolower($search);
        return $items->filter(function (Siswa $siswa) use ($searchLower) {
            return str_contains(mb_strtolower((string) $siswa->nama), $searchLower)
                || str_contains(mb_strtolower((string) $siswa->nis), $searchLower);
        })->values();
    }

    private function filterSuffix(Request $request): string
    {
        $search = trim((string) $request->input('search', ''));
        $kelas = trim((string) $request->input('kelas', ''));

        $parts = [];
        if ($kelas !== '') {
            $parts[] = Str::slug($kelas);
        }
        if ($search !== '') {
            $parts[] = Str::slug($search);
        }

        return $parts === [] ? '-semua' : '-' . implode('-', $parts);
    }

    private function buildQrPng(string $nis, int $size)
    {
        // Error correction diturunkan ke Medium (bukan High) supaya payload
        // pendek (NIS saja) tetap muat di QR version 1 / 21x21 modul.
        // Kapasitas version 1 level Medium: 34 digit angka atau 20 karakter
        // alfanumerik. Kalau NIS di sekolahmu lebih panjang dari itu atau
        // mengandung huruf, encoder akan otomatis naik ke version 2+ —
        // itu wajar, versi QR selalu mengikuti panjang data, tidak bisa
        // dipaksa version 1 kalau datanya tidak muat.
        return (new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $nis,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        ))->build();
    }

    private function importPath(string $token): string
    {
        return 'imports/siswa/' . $token . '.json';
    }

    private function forgetImportPreview(): void
    {
        $token = session(self::IMPORT_SESSION_KEY);
        if ($token && preg_match('/^[A-Za-z0-9]+$/', $token)) {
            Storage::disk(self::IMPORT_DISK)->delete($this->importPath($token));
        }
        session()->forget(self::IMPORT_SESSION_KEY);
    }
}