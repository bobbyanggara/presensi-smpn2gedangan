<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $fillable = ['nis', 'nama', 'kelas', 'foto'];

    public function absensis()
    {
        return $this->hasMany(Absensi::class);
    }
}
