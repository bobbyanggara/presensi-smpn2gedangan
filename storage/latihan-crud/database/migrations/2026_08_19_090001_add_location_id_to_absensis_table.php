<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('absensis', 'location_id')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->foreignId('location_id')
                    ->nullable()
                    ->after('siswa_id')
                    ->constrained('attendance_locations')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('absensis', 'location_id')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->dropConstrainedForeignId('location_id');
            });
        }
    }
};
