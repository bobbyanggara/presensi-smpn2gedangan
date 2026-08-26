<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('siswas', 'barcode_token')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropUnique(['barcode_token']);
                $table->dropColumn('barcode_token');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('siswas', 'barcode_token')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('barcode_token', 64)->nullable()->unique()->after('nis');
            });
        }
    }
};
