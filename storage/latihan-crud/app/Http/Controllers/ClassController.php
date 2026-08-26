<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Siswa;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    /**
     * Halaman Kelola Data Kelas. Menampilkan daftar kelas beserta jumlah
     * siswa di masing-masing kelas (dicocokkan lewat kolom teks "kelas"
     * pada tabel siswas).
     */
    public function index(Request $request)
    {
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

    public function destroy(ClassRoom $class)
    {
        $studentCount = Siswa::where('kelas', $class->name)->count();

        if ($studentCount > 0) {
            return redirect()->route('classes.index')
                ->with('error', "Kelas \"{$class->name}\" masih memiliki {$studentCount} siswa. Pindahkan atau hapus siswa tersebut terlebih dahulu sebelum menghapus kelas.");
        }

        $class->delete();

        return redirect()->route('classes.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
