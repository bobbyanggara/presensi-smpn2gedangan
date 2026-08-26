<?php

namespace App\Http\Controllers;

use App\Exports\SiswaTemplateExport;
use App\Imports\SiswaImport;
use App\Models\ClassRoom;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
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

        $siswas = $this->filteredSiswaQuery($request)
            ->orderBy('nama')
            ->paginate(50)
            ->withQueryString();

        $kelasOptions = $this->kelasOptions();

        return view('siswa.index', compact('siswas', 'search', 'kelas', 'kelasOptions'));
    }

    /**
     * Halaman "Export QR" — dipisah dari Data Siswa dan punya menu sendiri
     * di sidebar. Berisi filter kelas/nama dan tombol unduh PNG/PDF QR
     * barcode siswa secara massal.
     */
    public function exportQr(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $kelas = trim((string) $request->input('kelas', ''));

        $matchedCount = $this->filteredSiswaQuery($request)
            ->whereNotNull('nis')
            ->where('nis', '<>', '')
            ->count();

        $totalWithNis = Siswa::whereNotNull('nis')->where('nis', '<>', '')->count();
        $totalWithoutNis = Siswa::query()->count() - $totalWithNis;

        $kelasOptions = $this->kelasOptions();

        return view('siswa.export-qr', compact('search', 'kelas', 'kelasOptions', 'matchedCount', 'totalWithoutNis'));
    }

    /**
     * Pilihan dropdown kelas diambil dari master data kelas (tabel classes,
     * kelas aktif saja) supaya penulisan nama kelas konsisten dan tidak lagi
     * bergantung pada nilai unik kolom teks siswas.kelas yang rawan typo.
     * Kalau master kelas masih kosong, jatuhkan ke nilai unik kolom kelas
     * siswa sebagai cadangan supaya filter tetap bisa dipakai.
     */
    private function kelasOptions()
    {
        if (Schema::hasTable('classes')) {
            $fromMaster = ClassRoom::query()
                ->where('status', true)
                ->orderBy('grade')
                ->orderBy('name')
                ->pluck('name');

            if ($fromMaster->isNotEmpty()) {
                return $fromMaster;
            }
        }

        return Siswa::query()
            ->whereNotNull('kelas')
            ->where('kelas', '<>', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');
    }

    public function create()
    {
        $kelasOptions = $this->kelasOptions();

        return view('siswa.create', compact('kelasOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nis' => ['required', 'string', 'max:30', 'unique:siswas,nis'],
            'nama' => ['required', 'string', 'max:150'],
            'kelas' => ['required', 'string', 'max:50'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('foto') && Schema::hasColumn('siswas', 'foto')) {
            $data['foto'] = $request->file('foto')->store('uploads/siswa', 'public');
        }

        Siswa::create($data);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function show(Siswa $siswa)
    {
        // Tidak ada halaman detail terpisah di UI (semua link mengarah ke
        // Edit), jadi arahkan ke sana supaya URL /siswa/{id} tetap berguna
        // alih-alih menampilkan error "view not found".
        return redirect()->route('siswa.edit', $siswa);
    }

    public function edit(Siswa $siswa)
    {
        $kelasOptions = $this->kelasOptions();

        return view('siswa.edit', compact('siswa', 'kelasOptions'));
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
            'nis' => ['required', 'string', 'max:30', 'unique:siswas,nis,' . $siswa->id],
            'nama' => ['required', 'string', 'max:150'],
            'kelas' => ['required', 'string', 'max:50'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

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

        $nisList = array_values(array_unique(array_column($rows, 'nis')));
        $existing = Siswa::whereIn('nis', $nisList)->pluck('id', 'nis')->all();

        $newCount = 0;
        $updateCount = 0;
        foreach ($rows as &$row) {
            $row['_action'] = isset($existing[$row['nis']]) ? 'UPDATE' : 'BARU';
            $row['_existing_id'] = $existing[$row['nis']] ?? null;
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

            if ($errors !== [] || $rows === []) {
                return redirect()->route('siswa.import')->with('error', 'Import dibatalkan karena data masih memiliki masalah.');
            }

            DB::transaction(function () use ($rows) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    $now = now();
                    $data = array_map(static function (array $row) use ($now) {
                        return [
                            'nis' => $row['nis'],
                            'nama' => $row['nama'],
                            'kelas' => $row['kelas'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }, $chunk);

                    Siswa::upsert($data, ['nis'], ['nama', 'kelas', 'updated_at']);
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

    public function downloadTemplate()
    {
        return Excel::download(new SiswaTemplateExport(), 'template-data-siswa.xlsx');
    }

    public function barcodePng(Siswa $siswa)
    {
        if (blank($siswa->nis)) {
            return redirect()->route('siswa.index')->with('error', 'QR tidak dapat dibuat karena NIS siswa belum diisi. Silakan lengkapi NIS terlebih dahulu.');
        }

        $result = $this->buildQrPng($siswa->nis, 600);

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
        if (blank($siswa->nis)) {
            return redirect()->route('siswa.index')->with('error', 'PDF tidak dapat dibuat karena NIS siswa belum diisi. Silakan lengkapi NIS terlebih dahulu.');
        }

        $qr = $this->buildQrPng($siswa->nis, 320);

        $items = collect([[
            'siswa' => $siswa,
            'qrDataUri' => 'data:' . $qr->getMimeType() . ';base64,' . base64_encode($qr->getString()),
        ]]);

        try {
            $pdf = Pdf::loadView('siswa.barcode-pdf', ['items' => $items])
                ->setPaper('a4', 'portrait');

            return $pdf->download('qr-' . $siswa->nis . '.pdf');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('siswa.index')->with('error', 'Gagal membuat PDF QR siswa. Periksa log aplikasi untuk detail teknis.');
        }
    }

    public function barcodePngAll(Request $request)
    {
        $siswas = $this->filteredSiswaQuery($request)
            ->whereNotNull('nis')
            ->where('nis', '<>', '')
            ->orderBy('nama')
            ->get(['id', 'nis', 'nama']);

        return response()->json([
            'count' => $siswas->count(),
            'items' => $siswas->map(fn (Siswa $siswa) => [
                'nis' => (string) $siswa->nis,
                'nama' => $siswa->nama,
                'url' => route('siswa.barcode.png', $siswa),
            ])->values(),
        ]);
    }

    public function barcodePdfAll(Request $request)
    {
        $siswas = $this->filteredSiswaQuery($request)
            ->whereNotNull('nis')
            ->where('nis', '<>', '')
            ->orderBy('nama')
            ->get(['id', 'nis', 'nama', 'kelas']);

        if ($siswas->isEmpty()) {
            return redirect()->route('siswa.index')->with('error', 'Tidak ada siswa dengan NIS yang cocok dengan filter untuk dibuatkan QR.');
        }

        // PDF gabungan bisa berisi banyak QR (gambar base64), jadi longgarkan
        // batas waktu & memori agar tidak berhenti diam-diam di tengah proses.
        @set_time_limit(300);
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        try {
            $items = $siswas->map(function (Siswa $siswa) {
                try {
                    // Gunakan PNG base64 agar hasil PDF konsisten di DomPDF dan tidak
                    // bergantung pada dukungan renderer SVG.
                    $qr = $this->buildQrPng($siswa->nis, 320);
                } catch (\Throwable $e) {
                    throw new \RuntimeException('Gagal generate QR untuk NIS ' . $siswa->nis . ': ' . $e->getMessage(), 0, $e);
                }

                return [
                    'siswa' => $siswa,
                    'qrDataUri' => 'data:' . $qr->getMimeType() . ';base64,' . base64_encode($qr->getString()),
                ];
            });

            $pdf = Pdf::loadView('siswa.barcode-pdf', [
                'items' => $items,
            ])->setPaper('a4', 'portrait');

            $suffix = $this->filterSuffix($request);

            return $pdf->download('qr-siswa' . $suffix . '-' . now()->format('Y-m-d-His') . '.pdf');
        } catch (\Throwable $e) {
            report($e);

            $message = 'Gagal membuat PDF QR gabungan. Coba filter kelas/nama agar jumlah siswa lebih sedikit, lalu unduh ulang.';
            if (config('app.debug')) {
                $message .= ' [DEBUG] ' . get_class($e) . ': ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')';
            }

            return redirect()->route('siswa.index')->with('error', $message);
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

        return Siswa::query()
            ->when($search !== '', function ($query) use ($search) {
                $escaped = addcslashes($search, '%_\\');
                $query->where(function ($query) use ($escaped) {
                    $query->where('nis', 'like', $escaped . '%')
                        ->orWhere('nama', 'like', '%' . $escaped . '%');
                });
            })
            // Cocokkan kelas tanpa peduli besar/kecil huruf dan spasi berlebih
            // di ujung, supaya data lama yang penulisannya sedikit berbeda
            // ("IX A" vs "ix a ") tetap ketemu saat difilter.
            ->when($kelas !== '', fn ($query) => $query->whereRaw('LOWER(TRIM(kelas)) = ?', [mb_strtolower($kelas)]));
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
        return (new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $nis,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 12,
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
