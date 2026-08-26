<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('siswas', 'nis')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('nis', 30)->nullable()->unique()->after('id');
            });
        }

        if (Schema::hasColumn('siswas', 'rfid_uid')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropUnique(['rfid_uid']);
                $table->dropIndex(['rfid_uid']);
                $table->dropColumn('rfid_uid');
            });
        }

        foreach (['jurusan', 'no_hp_ortu', 'foto'] as $column) {
            if (Schema::hasColumn('siswas', $column)) {
                Schema::table('siswas', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('siswas', 'nis')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropUnique(['nis']);
                $table->dropColumn('nis');
            });
        }

        if (!Schema::hasColumn('siswas', 'jurusan')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('jurusan')->nullable()->after('kelas');
            });
        }

        if (!Schema::hasColumn('siswas', 'rfid_uid')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('rfid_uid')->nullable()->unique()->after('jurusan');
            });
        }

        if (!Schema::hasColumn('siswas', 'no_hp_ortu')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('no_hp_ortu')->nullable()->after('rfid_uid');
            });
        }

        if (!Schema::hasColumn('siswas', 'foto')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('foto')->nullable()->after('no_hp_ortu');
            });
        }
    }
};
