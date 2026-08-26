<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
        });

        // Isi username untuk user yang sudah ada (dari bagian sebelum "@" pada email),
        // supaya akun lama tetap bisa login setelah kolom ini jadi wajib & unik.
        DB::table('users')->whereNull('username')->orderBy('id')->each(function ($user) {
            $base = Str::slug(Str::before($user->email, '@'), '');
            $base = $base !== '' ? $base : 'user' . $user->id;
            $username = $base;
            $suffix = 1;

            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $base . $suffix;
                $suffix++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
