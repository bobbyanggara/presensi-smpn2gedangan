<?php

namespace App\Exports;

use App\Models\Siswa;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export daftar data siswa (NIS, nama, kelas) ke Excel.
 * Ini terpisah dari fitur QR Code — hanya berisi data, tanpa gambar QR.
 */
class SiswaDataExport implements FromCollection, WithHeadings, WithMapping
{
    protected string $search;
    protected string $kelas;

    public function __construct(string $search = '', string $kelas = '')
    {
        $this->search = $search;
        $this->kelas = $kelas;
    }

    public function collection(): Collection
    {
        $items = Siswa::query()
            ->when($this->kelas !== '', fn ($query) => $query->where('kelas', $this->kelas))
            ->orderBy('nama')
            ->get();

        if ($this->search === '') {
            return $items;
        }

        $search = mb_strtolower($this->search);
        return $items->filter(fn (Siswa $siswa) =>
            str_contains(mb_strtolower((string) $siswa->nama), $search)
            || str_contains(mb_strtolower((string) $siswa->nis), $search)
        )->values();
    }

    public function headings(): array
    {
        return ['NIS', 'Nama', 'Kelas'];
    }

    public function map($siswa): array
    {
        return [
            $siswa->nis,
            $siswa->nama,
            $siswa->kelas,
        ];
    }
}
