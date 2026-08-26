<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel master data kelas. Dipisah dari kolom teks bebas "kelas" di
     * tabel siswas supaya nama kelas konsisten (tidak ada lagi "IX A" vs
     * "9A" vs "9 a" yang bikin filter/dropdown pecah).
     *
     * Sebagian instalasi sudah punya tabel "classes" lama peninggalan
     * percobaan sebelumnya, dengan siswas.class_id yang foreign key ke
     * classes.id. Foreign key itu diputus dulu (kolom class_id di siswas
     * TIDAK dihapus/disentuh datanya) supaya DROP TABLE classes tidak
     * gagal karena constraint, baru tabel classes dibuat ulang dengan
     * struktur yang benar.
     */
    public function up(): void
    {
        if (Schema::hasTable('siswas') && Schema::hasColumn('siswas', 'class_id')) {
            $foreignKeys = collect(DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'siswas'
                 AND COLUMN_NAME = 'class_id'
                 AND REFERENCED_TABLE_NAME IS NOT NULL"
            ));

            foreach ($foreignKeys as $fk) {
                Schema::table('siswas', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            }
        }

        Schema::dropIfExists('classes');

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // contoh: IX A
            $table->string('grade', 10)->nullable(); // contoh: IX / 9
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
