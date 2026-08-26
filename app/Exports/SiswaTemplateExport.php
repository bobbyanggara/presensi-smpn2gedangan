<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SiswaTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['NIS', 'Nama', 'Kelas'];
    }

    public function array(): array
    {
        return [
            ['240001', 'Contoh Nama Siswa', 'IX A'],
        ];
    }
}
