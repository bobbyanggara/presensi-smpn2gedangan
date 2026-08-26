<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Siswa;
use App\Models\AttendanceLocation;
use App\Exports\AbsensiExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbsensiController extends Controller
{
    // Halaman buat scan kartu
    public function scan()
    {
        $locations = AttendanceLocation::where('status', true)->orderBy('name')->get();

        return view('absensi.scan', compact('locations'));
    }

    // Proses waktu QR/barcode di-scan (atau NIS diketik manual)
    public function proses(Request $request)
    {
        $request->validate([
            'nis' => 'required',
            'location_id' => 'required',
        ]);

        // NIS disimpan terenkripsi di database (lihat Siswa::setNisAttribute),
        // jadi tidak bisa dicari langsung lewat where('nis', ...) karena hasil
        // enkripsinya acak tiap kali disimpan. findByNis() sudah menangani ini
        // dengan decrypt & bandingkan di aplikasi.
        $siswa = Siswa::findByNis($request->nis);

        if (!$siswa) {
            $message = 'NIS tidak terdaftar';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 404);
            }

            return back()->with('error', $message);
        }

        $location = AttendanceLocation::find($request->location_id);

        $hariIni = Carbon::now()->format('Y-m-d');

        $sudahAbsen = Absensi::where('siswa_id', $siswa->id)
            ->where('tanggal', $hariIni)
            ->where('location_id', $request->location_id)
            ->first();

        if ($sudahAbsen) {
            $message = $siswa->nama . ' sudah absen hari ini pukul ' . $sudahAbsen->jam_masuk;
            $popup = [
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'kelas' => $siswa->kelas,
                'foto_url' => $siswa->foto ? route('siswa.foto', $siswa) : null,
                'location_name' => $location->name ?? null,
                'status' => 'sudah',
                'jam' => $sudahAbsen->jam_masuk,
            ];

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message, 'popup' => $popup]);
            }

            return back()->with('error', $message)->with('popup_siswa', $popup);
        }

        $jamSekarang = Carbon::now()->format('H:i:s');
        $status = 'hadir';

        Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => $hariIni,
            'jam_masuk' => $jamSekarang,
            'status' => $status,
            'location_id' => $request->location_id,
        ]);

        // Kirim notifikasi WhatsApp SETELAH response dibalas ke browser.
        // Sebelumnya ini dipanggil langsung (synchronous) sehingga operator harus
        // menunggu API Fonnte selesai merespons (bisa 1-5 detik) sebelum kartu
        // berikutnya bisa di-scan. Dengan ->afterResponse(), request langsung
        // dibalas duluan, baru WA dikirim di background proses yang sama.
        dispatch(function () use ($siswa, $status, $jamSekarang) {
            $this->kirimNotifikasiWhatsapp($siswa, $status, $jamSekarang);
        })->afterResponse();

        $message = 'Absen berhasil: ' . $siswa->nama . ' (' . $status . ')';
        $popup = [
            'nama' => $siswa->nama,
            'nis' => $siswa->nis,
            'kelas' => $siswa->kelas,
            'foto_url' => $siswa->foto ? route('siswa.foto', $siswa) : null,
            'location_name' => $location->name ?? null,
            'status' => $status,
            'jam' => $jamSekarang,
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'popup' => $popup]);
        }

        return back()->with('success', $message)->with('popup_siswa', $popup);
    }

    // Fungsi khusus kirim WA lewat Fonnte
    private function kirimNotifikasiWhatsapp(Siswa $siswa, string $status, string $jamMasuk)
    {
        if (!$siswa->no_hp_ortu) {
            return;
        }

        $statusText = $status === 'telat' ? 'TELAT' : 'HADIR';

        $pesan = "Halo, kami informasikan bahwa ananda *{$siswa->nama}* ({$siswa->kelas}) "
            . "telah tercatat *{$statusText}* di sekolah pada pukul {$jamMasuk} hari ini.\n\n"
            . "Pesan ini dikirim otomatis oleh Sistem Absensi Sekolah.";

        try {
            Http::withHeaders([
                'Authorization' => config('services.fonnte.token'),
            ])->post('https://api.fonnte.com/send', [
                'target' => $siswa->no_hp_ortu,
                'message' => $pesan,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal kirim WA Fonnte: ' . $e->getMessage());
        }
    }

    // Riwayat absensi hari ini di satu lokasi, dipakai oleh panel "Riwayat Absensi"
    // di halaman scan (fetch via JS, lihat resources/views/absensi/scan.blade.php).
    // Tanpa location_id, kosongkan hasil (operator wajib pilih lokasi dulu).
    public function liveFeed(Request $request)
    {
        $locationId = $request->input('location_id');

        if (!$locationId) {
            return response()->json(['data' => []]);
        }

        $hariIni = Carbon::now()->format('Y-m-d');

        $absensis = Absensi::with('siswa')
            ->where('tanggal', $hariIni)
            ->where('location_id', $locationId)
            ->orderBy('jam_masuk', 'desc')
            ->get();

        return response()->json([
            'data' => $absensis->map(function ($absen) {
                return [
                    'nama' => $absen->siswa->nama ?? '-',
                    'kelas' => $absen->siswa->kelas ?? '-',
                    'nis' => $absen->siswa->nis ?? '-',
                    'jam' => $absen->jam_masuk,
                    'foto_url' => ($absen->siswa && $absen->siswa->foto)
                        ? route('siswa.foto', $absen->siswa)
                        : null,
                ];
            }),
        ]);
    }

    // Query dasar rekap hari ini, dipakai bareng oleh rekap() (halaman HTML)
    // dan rekapData() (endpoint JSON buat polling realtime).
    private function rekapQuery(Request $request)
    {
        $hariIni = Carbon::now()->format('Y-m-d');

        $query = Absensi::with(['siswa', 'location'])
            ->where('tanggal', $hariIni);

        if ($request->filled('kelas')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas', $request->input('kelas'));
            });
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        return $query->orderBy('jam_masuk', 'desc');
    }

    // Halaman rekap absensi hari ini
    public function rekap(Request $request)
    {
        $absensis = $this->rekapQuery($request)->get();

        $kelasOptions = Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $locations = AttendanceLocation::where('status', true)->orderBy('name')->get();
        $kelas = $request->input('kelas', '');
        $locationId = $request->input('location_id', '');

        return view('absensi.rekap', compact('absensis', 'kelasOptions', 'locations', 'kelas', 'locationId'));
    }

    // Endpoint JSON buat polling realtime di halaman rekap (dipanggil via fetch() tiap beberapa detik)
    public function rekapData(Request $request)
    {
        $absensis = $this->rekapQuery($request)->get();

        return response()->json([
            'count' => $absensis->count(),
            'rows' => $absensis->map(function ($absen) {
                return [
                    'nama' => $absen->siswa->nama ?? '-',
                    'kelas' => $absen->siswa->kelas ?? '-',
                    'lokasi' => $absen->location->name ?? '-',
                    'jam_masuk' => $absen->jam_masuk,
                    'status' => $absen->status,
                ];
            }),
        ]);
    }

    // Export rekap absensi HARI INI ke Excel, ikut filter kelas & lokasi yang
    // sedang aktif di halaman (lihat RekapHarianExport). Beda dengan export()
    // di bawah yang untuk laporan bulanan.
    public function rekapExport(Request $request)
    {
        $kelas = $request->input('kelas', '');
        $locationId = $request->input('location_id', '');

        $tanggal = Carbon::now()->format('d-m-Y');
        $namaFile = 'rekap-absensi-' . $tanggal
            . ($kelas !== '' ? '-' . \Illuminate\Support\Str::slug($kelas) : '')
            . '.xlsx';

        return Excel::download(
            new \App\Exports\RekapHarianExport($kelas ?: null, $locationId ?: null),
            $namaFile
        );
    }

    // Halaman laporan bulanan (dengan filter bulan, tahun, kelas & lokasi)
    public function laporanBulanan(Request $request)
    {
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));
        $kelas = $request->input('kelas', '');
        $locationId = $request->input('location_id', '');

        $absensis = $this->laporanQuery($request)->get();

        $kelasOptions = Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $locations = AttendanceLocation::where('status', true)->orderBy('name')->get();

        return view('absensi.laporan', compact(
            'absensis', 'bulan', 'tahun', 'kelas', 'locationId', 'kelasOptions', 'locations'
        ));
    }

    // Query dasar laporan bulanan, dipakai bareng oleh laporanBulanan() (halaman HTML)
    // dan laporanData() (endpoint JSON buat polling realtime).
    private function laporanQuery(Request $request)
    {
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));

        $query = Absensi::with(['siswa', 'location'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun);

        if ($request->filled('kelas')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas', $request->input('kelas'));
            });
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        return $query->orderBy('tanggal', 'asc');
    }

    // Endpoint JSON buat polling realtime di halaman laporan bulanan.
    // Hanya berguna kalau bulan/tahun yang sedang dilihat adalah bulan berjalan
    // (laporan bulan lalu datanya sudah final, tidak akan berubah).
    public function laporanData(Request $request)
    {
        $absensis = $this->laporanQuery($request)->get();

        return response()->json([
            'count' => $absensis->count(),
            'rows' => $absensis->map(function ($absen) {
                return [
                    'tanggal' => \Carbon\Carbon::parse($absen->tanggal)->format('d-m-Y'),
                    'nama' => $absen->siswa->nama ?? '-',
                    'kelas' => $absen->siswa->kelas ?? '-',
                    'lokasi' => $absen->location->name ?? '-',
                    'jam_masuk' => $absen->jam_masuk,
                    'status' => $absen->status,
                ];
            }),
        ]);
    }

    // Export laporan bulanan ke Excel
    public function export(Request $request)
    {
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));

        $namaFile = 'absensi_' . $bulan . '_' . $tahun . '.xlsx';

        return Excel::download(new AbsensiExport($bulan, $tahun), $namaFile);
    }

    // Kumpulan data dashboard, dipakai bareng oleh dashboard() (load awal halaman)
    // dan dashboardFeed() (endpoint JSON buat polling realtime tiap 5 detik).
    private function buildDashboardData(): array
    {
        $hariIni = Carbon::now()->format('Y-m-d');

        $totalSiswa = Siswa::count();

        $absenHariIni = Absensi::where('tanggal', $hariIni)->get();

        // Dihitung per siswa unik, bukan per baris absensi — karena 1 siswa bisa
        // punya lebih dari 1 baris absensi hari ini kalau absen di beberapa lokasi.
        $jumlahHadir = $absenHariIni->pluck('siswa_id')->unique()->count();
        $jumlahBelumAbsen = max(0, $totalSiswa - $jumlahHadir);
        $persentaseHadir = $totalSiswa > 0 ? round(($jumlahHadir / $totalSiswa) * 100) : 0;

        // Tren 7 hari terakhir (termasuk hari ini), dihitung per siswa unik per hari.
        $trenMingguan = [];
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::now()->subDays($i);
            $jumlahHariItu = Absensi::where('tanggal', $tanggal->format('Y-m-d'))
                ->distinct('siswa_id')
                ->count('siswa_id');

            $trenMingguan[] = [
                'jumlah' => $jumlahHariItu,
                'label' => $tanggal->translatedFormat('D'),
                'is_today' => $tanggal->isToday(),
            ];
        }
        $maxTren = max(1, collect($trenMingguan)->max('jumlah'));

        // Kehadiran per lokasi aktif, dihitung per siswa unik hari ini.
        $locations = AttendanceLocation::where('status', true)->orderBy('name')->get();
        $lokasiStats = $locations->map(function ($location) use ($hariIni) {
            return [
                'nama' => $location->name,
                'jumlah' => Absensi::where('tanggal', $hariIni)
                    ->where('location_id', $location->id)
                    ->distinct('siswa_id')
                    ->count('siswa_id'),
            ];
        })->values()->all();

        $absensiTerbaru = Absensi::with(['siswa', 'location'])
            ->where('tanggal', $hariIni)
            ->orderBy('jam_masuk', 'desc')
            ->take(8)
            ->get();

        return compact(
            'totalSiswa', 'jumlahHadir', 'jumlahBelumAbsen', 'persentaseHadir',
            'trenMingguan', 'maxTren', 'lokasiStats', 'absensiTerbaru'
        );
    }

    // Dashboard ringkasan
    public function dashboard()
    {
        return view('dashboard', $this->buildDashboardData());
    }

    // Endpoint JSON buat polling realtime di halaman dashboard (dipanggil tiap 5 detik)
    public function dashboardFeed()
    {
        $data = $this->buildDashboardData();

        return response()->json([
            'jumlah_hadir' => $data['jumlahHadir'],
            'persentase_hadir' => $data['persentaseHadir'],
            'tren_mingguan' => $data['trenMingguan'],
            'max_tren' => $data['maxTren'],
            'lokasi_stats' => $data['lokasiStats'],
            'absensi_terbaru' => $data['absensiTerbaru']->map(function ($absen) {
                return [
                    'id' => $absen->id,
                    'nama' => $absen->siswa->nama ?? '-',
                    'kelas' => $absen->siswa->kelas ?? '-',
                    'lokasi' => $absen->location->name ?? '-',
                    'jam' => $absen->jam_masuk,
                    'status' => $absen->status,
                    'foto_url' => ($absen->siswa && $absen->siswa->foto)
                        ? route('siswa.foto', $absen->siswa)
                        : null,
                ];
            }),
        ]);
    }

    // Daftar siswa yang belum absen hari ini
    public function belumAbsen()
    {
        $hariIni = Carbon::now()->format('Y-m-d');

        $idSudahAbsen = Absensi::where('tanggal', $hariIni)->pluck('siswa_id');

        $siswaBelumAbsen = Siswa::whereNotIn('id', $idSudahAbsen)
            ->orderBy('nama', 'asc')
            ->get();

        return view('absensi.belum-absen', compact('siswaBelumAbsen'));
    }
}
