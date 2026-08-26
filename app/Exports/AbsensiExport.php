<?php

namespace App\Exports;

use App\Models\Absensi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AbsensiExport implements FromCollection, WithHeadings, WithMapping
{
    protected $bulan;
    protected $tahun;
    protected $locationId;
    protected $kelas;

    public function __construct($bulan, $tahun, $locationId = null, $kelas = null)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->locationId = $locationId;
        $this->kelas = $kelas;
    }

    public function collection()
    {
        return Absensi::with(['siswa', 'location'])
            ->whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->when($this->locationId, fn ($q) => $q->where('location_id', $this->locationId))
            ->when($this->kelas, fn ($q) => $q->whereHas('siswa', fn ($sq) => $sq->where('kelas', $this->kelas)))
            ->orderBy('tanggal', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Lokasi',
            'Jam Masuk',
            'Status',
        ];
    }

    public function map($absen): array
    {
        return [
            \Carbon\Carbon::parse($absen->tanggal)->format('d-m-Y'),
            $absen->siswa->nis,
            $absen->siswa->nama,
            $absen->siswa->kelas,
            $absen->location->name ?? '-',
            $absen->jam_masuk,
            $absen->status,
        ];
    }
}