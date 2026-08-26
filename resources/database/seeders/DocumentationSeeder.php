<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AttendanceLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DocumentationSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            ['start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true]
        );

        if (!$year->is_active) {
            $year->update(['is_active' => true]);
        }

        AttendanceLocation::firstOrCreate(
            ['name' => 'Perpustakaan'],
            ['is_required' => false, 'description' => 'Kunjungan perpustakaan; tidak wajib hadir.', 'status' => true]
        );
        AttendanceLocation::firstOrCreate(
            ['name' => 'Masjid'],
            ['is_required' => true, 'description' => 'Absensi wajib berdasarkan sesi/jadwal.', 'status' => true]
        );
    }
}
