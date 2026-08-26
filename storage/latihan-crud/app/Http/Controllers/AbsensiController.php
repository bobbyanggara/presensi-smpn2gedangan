<?php

namespace App\Http\Controllers;

use App\Exports\AbsensiExport;
use App\Models\Absensi;
use App\Models\AttendanceLocation;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AbsensiController extends Controller
{
    public function scan()
    {
        $locations = AttendanceLocation::where('status', true)->orderBy('name')->get();
        $selectedLocationId = session('scan_location_id');

        return view('absensi.scan', compact('locations', 'selectedLocationId'));
    }

    public function proses(Request $request)
    {
        $request->validate([
            'nis' => ['required', 'string', 'max:30'],
            'location_id' => ['required', 'exists:attendance_locations,id'],
        ]);

        // Ingat lokasi terakhir dipilih supaya operator nggak perlu pilih ulang tiap scan.
        session(['scan_location_id' => $request->input('location_id')]);

        $nis = trim($request->input('nis'));
        $siswa = Siswa::where('nis', $nis)->first();

        if (!$siswa) {
            $message = 'NIS tidak terdaftar.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 404);
            }
            return back()->with('error', $message);
        }

        $hariIni = Carbon::today()->toDateString();
        $jamSekarang = Carbon::now()->format('H:i:s');
        $status = $jamSekarang > '07:30:00' ? 'telat' : 'hadir';
        $locationId = $request->input('location_id');

        $sudahAbsen = Absensi::where('siswa_id', $siswa->id)
            ->where('tanggal', $hariIni)
            ->where('location_id', $locationId)
            ->first();

        $lokasi = AttendanceLocation::find($locationId);
        $namaLokasi = $lokasi->name ?? 'lokasi ini';

        if ($sudahAbsen) {
            return $this->attendanceResponse($request, $siswa, [
                'status' => 'sudah',
                'jam' => $sudahAbsen->jam_masuk,
            ], $siswa->nama . ' sudah absen di ' . $namaLokasi . ' hari ini pukul ' . $sudahAbsen->jam_masuk, false);
        }

        try {
            Absensi::create([
                'siswa_id' => $siswa->id,
                'location_id' => $locationId,
                'tanggal' => $hariIni,
                'jam_masuk' => $jamSekarang,
                'status' => $status,
            ]);
        } catch (QueryException $e) {
            // Unique constraint melindungi dari dua scan bersamaan di lokasi yang sama.
            $sudahAbsen = Absensi::where('siswa_id', $siswa->id)
                ->where('tanggal', $hariIni)
                ->where('location_id', $locationId)
                ->first();

            if ($sudahAbsen) {
                return $this->attendanceResponse($request, $siswa, [
                    'status' => 'sudah',
                    'jam' => $sudahAbsen->jam_masuk,
                ], $siswa->nama . ' sudah absen di ' . $namaLokasi . ' hari ini pukul ' . $sudahAbsen->jam_masuk, false);
            }

            throw $e;
        }

        return $this->attendanceResponse(
            $request,
            $siswa,
            ['status' => $status, 'jam' => $jamSekarang],
            'Absen berhasil: ' . $siswa->nama . ' (' . $status . ')',
            true
        );
    }

    private function attendanceResponse(Request $request, Siswa $siswa, array $attendance, string $message, bool $success)
    {
        $popup = [
            'nis' => $siswa->nis,
            'nama' => $siswa->nama,
            'kelas' => $siswa->kelas,
            'foto_url' => $siswa->foto ? route('siswa.foto', $siswa) : null,
            'status' => $attendance['status'],
            'jam' => $attendance['jam'],
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'popup' => $popup,
            ], $success ? 200 : 409);
        }

        return back()
            ->with($success ? 'success' : 'error', $message)
            ->with('popup_siswa', $popup);
    }

    public function rekap(Request $request)
    {
        $hariIni = Carbon::today()->toDateString();
        $locationId = $request->input('location_id');

        $absensis = Absensi::with(['siswa', 'location'])
            ->where('tanggal', $hariIni)
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->orderBy('jam_masuk', 'desc')
            ->get();

        $locations = AttendanceLocation::orderBy('name')->get();

        return view('absensi.rekap', compact('absensis', 'locations', 'locationId'));
    }

    public function laporanBulanan(Request $request)
    {
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));
        $locationId = $request->input('location_id');

        $absensis = Absensi::with(['siswa', 'location'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam_masuk', 'asc')
            ->get();

        $locations = AttendanceLocation::orderBy('name')->get();

        return view('absensi.laporan', compact('absensis', 'bulan', 'tahun', 'locations', 'locationId'));
    }

    public function export(Request $request)
    {
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));
        $locationId = $request->input('location_id');
        $namaFile = 'absensi_' . $bulan . '_' . $tahun . '.xlsx';

        return Excel::download(new AbsensiExport($bulan, $tahun, $locationId), $namaFile);
    }

    public function dashboard()
    {
        $hariIni = Carbon::today()->toDateString();
        $totalSiswa = Siswa::count();
        $absenHariIni = Absensi::where('tanggal', $hariIni)->get();

        // Per siswa dihitung sekali per hari (ambil scan pertama), meski dia absen di beberapa lokasi.
        $absenPerSiswa = $absenHariIni->sortBy('jam_masuk')->unique('siswa_id');

        $jumlahHadir = $absenPerSiswa->where('status', 'hadir')->count();
        $jumlahTelat = $absenPerSiswa->where('status', 'telat')->count();
        $jumlahSudahAbsen = $absenPerSiswa->count();
        $jumlahBelumAbsen = max(0, $totalSiswa - $jumlahSudahAbsen);
        $persentaseHadir = $totalSiswa > 0 ? round(($jumlahSudahAbsen / $totalSiswa) * 100) : 0;

        $absensiTerbaru = Absensi::with(['siswa', 'location'])
            ->where('tanggal', $hariIni)
            ->orderBy('jam_masuk', 'desc')
            ->take(6)
            ->get();

        // Rekap per lokasi hari ini, supaya kelihatan mana titik absen yang paling ramai.
        $lokasiStats = AttendanceLocation::where('status', true)
            ->orderBy('name')
            ->get()
            ->map(function ($lokasi) use ($hariIni) {
                $count = Absensi::where('location_id', $lokasi->id)->where('tanggal', $hariIni)->count();
                return ['nama' => $lokasi->name, 'jumlah' => $count];
            });

        // Tren 7 hari terakhir (jumlah siswa unik yang absen per hari), untuk grafik mini.
        $trenMingguan = collect(range(6, 0))->map(function ($i) {
            $tanggal = Carbon::today()->subDays($i);
            $jumlah = Absensi::where('tanggal', $tanggal->toDateString())
                ->distinct('siswa_id')
                ->count('siswa_id');
            return [
                'label' => $tanggal->translatedFormat('D'),
                'tanggal' => $tanggal->translatedFormat('d/m'),
                'jumlah' => $jumlah,
                'is_today' => $tanggal->isToday(),
            ];
        });
        $maxTren = max(1, $trenMingguan->max('jumlah'));

        return view('dashboard', compact(
            'totalSiswa', 'jumlahHadir', 'jumlahTelat', 'jumlahBelumAbsen', 'absensiTerbaru',
            'persentaseHadir', 'jumlahSudahAbsen', 'lokasiStats', 'trenMingguan', 'maxTren'
        ));
    }

    public function belumAbsen()
    {
        $hariIni = Carbon::today()->toDateString();
        $idSudahAbsen = Absensi::where('tanggal', $hariIni)->pluck('siswa_id');

        $siswaBelumAbsen = Siswa::whereNotIn('id', $idSudahAbsen)
            ->orderBy('nama')
            ->paginate(50);

        return view('absensi.belum-absen', compact('siswaBelumAbsen'));
    }
}
