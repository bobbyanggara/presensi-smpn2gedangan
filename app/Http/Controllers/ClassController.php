<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClassController extends Controller
{
    /**
     * Halaman Kelola Data Kelas. Menampilkan daftar kelas beserta jumlah
     * siswa di masing-masing kelas (dicocokkan lewat kolom teks "kelas"
     * pada tabel siswas).
     */
    /**
     * Halaman Kelola Data Kelas. Menampilkan daftar kelas beserta jumlah
     * siswa di masing-masing kelas (dicocokkan lewat kolom teks "kelas"
     * pada tabel siswas).
     *
     * Sebelum menampilkan data, tabel master kelas otomatis disinkronkan
     * dari nilai kolom "kelas" yang ada di data siswa (kelas baru yang
     * belum terdaftar akan otomatis dibuat) — jadi admin tidak perlu klik
     * tombol sinkron manual lagi.
     */
    public function index(Request $request)
    {
        $this->syncClassesFromStudents();

        $search = trim((string) $request->input('search', ''));

        $classes = ClassRoom::query()
            ->withCount(['students'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('grade')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('classes.index', compact('classes', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:classes,name'],
            'grade' => ['nullable', 'string', 'max:10'],
            'status' => ['sometimes', 'boolean'],
        ]);

        $data['status'] = $request->boolean('status', true);

        ClassRoom::create($data);

        return redirect()->route('classes.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, ClassRoom $class)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:classes,name,' . $class->id],
            'grade' => ['nullable', 'string', 'max:10'],
            'status' => ['sometimes', 'boolean'],
        ]);

        $data['status'] = $request->boolean('status', true);

        $oldName = $class->name;
        $class->update($data);

        // Nama kelas berubah -> ikut perbarui kolom teks "kelas" di data
        // siswa yang sebelumnya memakai nama lama, supaya filter & data
        // siswa tetap konsisten dengan master kelas.
        if ($oldName !== $class->name) {
            Siswa::where('kelas', $oldName)->update(['kelas' => $class->name]);
        }

        return redirect()->route('classes.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    /**
     * Menghapus kelas SEKALIGUS seluruh siswa di dalamnya (dan riwayat
     * absensinya, lewat foreign key cascade di tabel absensis). Dulu
     * method ini menolak hapus kalau masih ada siswa; sekarang guru bisa
     * langsung hapus kelas yang sudah lulus tanpa hapus siswa satu-satu
     * dulu. Foto siswa di storage juga ikut dibersihkan.
     */
    public function destroy(ClassRoom $class)
    {
        $siswaDiKelas = Siswa::where('kelas', $class->name)->get(['id', 'foto']);
        $studentCount = $siswaDiKelas->count();

        DB::transaction(function () use ($class) {
            Siswa::where('kelas', $class->name)->delete();
            $class->delete();
        });

        foreach ($siswaDiKelas as $siswa) {
            if ($siswa->foto && Storage::disk('public')->exists($siswa->foto)) {
                Storage::disk('public')->delete($siswa->foto);
            }
        }

        $pesan = $studentCount > 0
            ? "Kelas \"{$class->name}\" beserta {$studentCount} siswa di dalamnya berhasil dihapus."
            : "Kelas \"{$class->name}\" berhasil dihapus.";

        return redirect()->route('classes.index')->with('success', $pesan);
    }

    /**
     * Isi tabel master kelas secara otomatis dari nilai kolom teks "kelas"
     * yang sudah ada di data siswa. Dipanggil otomatis setiap kali halaman
     * Kelola Data Kelas dibuka, supaya admin tidak perlu sinkron manual.
     */
    private function syncClassesFromStudents(): void
    {
        $existing = ClassRoom::pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->all();

        $namaKelasSiswa = Siswa::query()
            ->whereNotNull('kelas')
            ->where('kelas', '<>', '')
            ->distinct()
            ->pluck('kelas')
            ->map(fn ($k) => trim($k))
            ->filter(fn ($k) => $k !== '' && !in_array(mb_strtolower($k), $existing))
            ->unique(fn ($k) => mb_strtolower($k))
            ->values();

        if ($namaKelasSiswa->isEmpty()) {
            return;
        }

        foreach ($namaKelasSiswa as $nama) {
            // Tebak tingkat dari kata pertama, misal "IX A" -> "IX", "7B" -> "7".
            preg_match('/^([A-Za-z0-9]+)/', $nama, $m);
            $grade = $m[1] ?? null;

            ClassRoom::create([
                'name' => $nama,
                'grade' => $grade,
                'status' => true,
            ]);
        }
    }
}
