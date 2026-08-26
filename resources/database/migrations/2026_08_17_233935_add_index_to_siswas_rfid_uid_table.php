<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            // Index ini bikin pencarian "WHERE rfid_uid = ..." saat scan jadi cepat
            // meskipun data siswa sudah ribuan baris (tanpa index, DB harus scan
            // seluruh tabel setiap kali ada kartu di-scan).
            $table->index('rfid_uid');
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropIndex(['rfid_uid']);
        });
    }
};
