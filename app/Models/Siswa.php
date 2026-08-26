<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Siswa extends Model
{
    protected $fillable = [
        'nis',
        'nama',
        'kelas',
        'foto',
    ];

    /** NIS disimpan terenkripsi di database dan otomatis didecrypt saat dibaca. */
    public function setNisAttribute($value): void
    {
        $nis = trim((string) $value);
        $this->attributes['nis'] = $nis === '' ? null : Crypt::encryptString($nis);
    }

    public function getNisAttribute($value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // Kompatibilitas sementara untuk data plaintext sebelum migration.
            return $value;
        }
    }

    /**
     * Cari siswa berdasarkan NIS plaintext dengan decrypt di aplikasi.
     * Jumlah data saat ini sekitar 1.500 siswa, sehingga masih layak untuk
     * kebutuhan scan; tidak ada hash NIS di database.
     */
    public static function findByNis(string $nis): ?self
    {
        $nis = trim($nis);
        if ($nis === '') {
            return null;
        }

        return self::query()->get()->first(function (self $siswa) use ($nis) {
            return (string) $siswa->nis === $nis;
        });
    }

    public function absensis()
    {
        return $this->hasMany(Absensi::class);
    }
}
