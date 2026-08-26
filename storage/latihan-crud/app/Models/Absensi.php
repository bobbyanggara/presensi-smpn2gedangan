<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $fillable = ['siswa_id', 'location_id', 'tanggal', 'jam_masuk', 'status'];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function location()
    {
        return $this->belongsTo(AttendanceLocation::class, 'location_id');
    }
}