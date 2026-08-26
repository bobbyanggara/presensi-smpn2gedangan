<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jika versi sebelumnya sempat menambahkan nis_hash, bersihkan karena
        // versi ini memang sengaja hanya memakai encrypt/decrypt NIS.
        if (Schema::hasColumn('siswas', 'nis_hash')) {
            try {
                Schema::table('siswas', function (Blueprint $table) {
                    $table->dropUnique('siswas_nis_hash_unique');
                });
            } catch (\Throwable $e) {
                // Index mungkin belum pernah dibuat.
            }
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropColumn('nis_hash');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE siswas MODIFY nis VARCHAR(255) NULL');
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE siswas ALTER COLUMN nis TYPE VARCHAR(255)');
        }

        try {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropUnique(['nis']);
            });
        } catch (\Throwable $e) {
            // Index unique mungkin sudah tidak ada.
        }

        $rows = DB::table('siswas')->select('id', 'nis')->orderBy('id')->get();
        $seen = [];

        foreach ($rows as $row) {
            if ($row->nis === null || trim((string) $row->nis) === '') {
                continue;
            }

            $value = trim((string) $row->nis);
            try {
                $plain = Crypt::decryptString($value);
                if ($plain !== '') {
                    $value = trim($plain);
                }
            } catch (\Throwable $e) {
                // Masih plaintext, jadi enkripsi di bawah.
            }

            if (isset($seen[$value])) {
                throw new \RuntimeException("NIS duplikat ditemukan saat enkripsi: {$value} (ID {$row->id} dan {$seen[$value]}).");
            }
            $seen[$value] = $row->id;

            DB::table('siswas')->where('id', $row->id)->update([
                'nis' => Crypt::encryptString($value),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('siswas')->select('id', 'nis')->orderBy('id')->get();
        foreach ($rows as $row) {
            if ($row->nis === null || trim((string) $row->nis) === '') {
                continue;
            }
            try {
                $plain = Crypt::decryptString($row->nis);
            } catch (\Throwable $e) {
                $plain = $row->nis;
            }
            DB::table('siswas')->where('id', $row->id)->update(['nis' => $plain, 'updated_at' => now()]);
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE siswas MODIFY nis VARCHAR(30) NULL');
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE siswas ALTER COLUMN nis TYPE VARCHAR(30)');
        }

        try {
            Schema::table('siswas', function (Blueprint $table) {
                $table->unique('nis');
            });
        } catch (\Throwable $e) {
            // Index sudah ada.
        }
    }
};
