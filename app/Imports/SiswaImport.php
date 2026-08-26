<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SiswaImport implements ToCollection, WithHeadingRow
{
    public array $rows = [];
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        $seen = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $nis = trim((string) ($row['nis'] ?? ''));
            $nama = trim((string) ($row['nama'] ?? ''));
            $kelas = trim((string) ($row['kelas'] ?? ''));

            $item = [
                'nis' => $nis,
                'nama' => $nama,
                'kelas' => $kelas,
                '_line' => $line,
            ];

            if ($nis === '') {
                $this->errors[] = "Baris {$line}: NIS wajib diisi.";
            } elseif (isset($seen[$nis])) {
                $this->errors[] = "Baris {$line}: NIS {$nis} duplikat dengan baris {$seen[$nis]}.";
            } else {
                $seen[$nis] = $line;
            }

            if ($nama === '') {
                $this->errors[] = "Baris {$line}: Nama wajib diisi.";
            }

            if ($kelas === '') {
                $this->errors[] = "Baris {$line}: Kelas wajib diisi.";
            }

            if ($nis !== '' && $nama !== '' && $kelas !== '') {
                $this->rows[] = $item;
            }
        }
    }
}
