<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    protected $fillable = ['location_id', 'name', 'date', 'start_time', 'end_time', 'status'];
    protected $casts = ['date' => 'date'];

    public function location(): BelongsTo { return $this->belongsTo(AttendanceLocation::class, 'location_id'); }
    public function attendances(): HasMany { return $this->hasMany(Absensi::class, 'session_id'); }

    public function isActiveNow(): bool
    {
        if ($this->status !== 'active' || !$this->date || !$this->date->isToday()) return false;
        $now = now()->format('H:i:s');
        return $now >= $this->start_time && $now <= $this->end_time;
    }
}
