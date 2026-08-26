<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceLocation extends Model
{
    protected $fillable = ['name', 'is_required', 'description', 'status'];
    protected $casts = ['is_required' => 'boolean', 'status' => 'boolean'];

    public function sessions(): HasMany { return $this->hasMany(AttendanceSession::class, 'location_id'); }
    public function attendances(): HasMany { return $this->hasMany(Absensi::class, 'location_id'); }
}
