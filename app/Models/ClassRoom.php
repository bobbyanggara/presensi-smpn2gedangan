<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    protected $table = 'classes';

    protected $fillable = ['name', 'grade', 'status'];

    protected $casts = ['status' => 'boolean'];

    /**
     * Siswa yang kolom teks "kelas"-nya sama persis dengan nama kelas ini.
     * Bukan foreign key (siswas.kelas masih kolom teks), jadi relasi ini
     * hanya dipakai untuk menghitung jumlah siswa per kelas di halaman
     * Kelola Kelas.
     */
    public function students()
    {
        return $this->hasMany(Siswa::class, 'kelas', 'name');
    }
}
