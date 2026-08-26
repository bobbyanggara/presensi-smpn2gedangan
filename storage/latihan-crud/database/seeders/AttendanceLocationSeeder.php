<?php

namespace Database\Seeders;

use App\Models\AttendanceLocation;
use Illuminate\Database\Seeder;

class AttendanceLocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['name' => 'Perpustakaan', 'is_required' => false, 'status' => true],
            ['name' => 'Masjid', 'is_required' => false, 'status' => true],
        ];

        foreach ($locations as $location) {
            AttendanceLocation::firstOrCreate(
                ['name' => $location['name']],
                $location
            );
        }
    }
}
