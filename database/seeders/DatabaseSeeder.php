<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
                'user_verified_at' => now(),
            ]
        );
        User::updateOrCreate(
            ['email' => 'operator@example.com'],
            [
                'name' => 'Operator Sekolah',
                'username' => 'operator',
                'password' => \Illuminate\Support\Facades\Hash::make('operator123'),
                'email_verified_at' => now(),
            ]
        );

        $this->call(AttendanceLocationSeeder::class);
    }
}
