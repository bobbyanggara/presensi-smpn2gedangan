<?php

namespace Database\Seeders;

use App\Models\AttendanceLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DocumentationSeeder extends Seeder
{
    public function run(): void
    {
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
