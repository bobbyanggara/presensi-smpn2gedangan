<?php

namespace App\Exports;

use App\Models\Absensi;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export "Rekap Absensi" (kehadiran hari ini) ke Excel, sesuai filter kelas
 * dan lokasi yang sedang aktif di halaman — bukan laporan bulanan penuh
 * (lihat AbsensiExport untuk itu).
 */
class RekapHarianExport implements FromCollection, WithHeadings, WithMapping
{
    protected string $tanggal;
    protected ?string $kelas;
    protected ?string $locationId;

    public function __construct(?string $kelas = null, ?string $locationId = null)
    {
        $this->tanggal = Carbon::now()->format('Y-m-d');
        $this->kelas = $kelas;
        $this->locationId = $locationId;
    }

    public function collection()
    {
        return Absensi::with(['siswa', 'location'])
            ->where('tanggal', $this->tanggal)
            ->when($this->kelas, fn ($q) => $q->whereHas('siswa', fn ($sq) => $sq->where('kelas', $this->kelas)))
            ->when($this->locationId, fn ($q) => $q->where('location_id', $this->locationId))
            ->orderBy('jam_masuk', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nama Siswa',
            'NIS',
            'Kelas',
            'Lokasi',
            'Jam Masuk',
            'Status',
        ];
    }

    public function map($absen): array
    {
        return [
            $absen->siswa->nama ?? '-',
            $absen->siswa->nis ?? '-',
            $absen->siswa->kelas ?? '-',
            $absen->location->name ?? '-',
            $absen->jam_masuk,
            $absen->status,
        ];
    }
}
