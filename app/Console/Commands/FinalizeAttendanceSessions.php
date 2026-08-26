<?php

namespace App\Console\Commands;

use App\Models\AttendanceSession;
use App\Services\AttendanceSessionFinalizer;
use Illuminate\Console\Command;

class FinalizeAttendanceSessions extends Command
{
    protected $signature = 'attendance:finalize {--date= : Tanggal YYYY-MM-DD}';
    protected $description = 'Menutup sesi absensi wajib dan membuat record TIDAK HADIR untuk siswa yang tidak scan.';

    public function handle(AttendanceSessionFinalizer $finalizer): int
    {
        $date = $this->option('date') ?: today()->toDateString();
        $sessions = AttendanceSession::with('location')
            ->whereDate('date', $date)
            ->where('status', '!=', 'finished')
            ->whereHas('location', fn ($q) => $q->where('is_required', true))
            ->get();

        foreach ($sessions as $session) {
            $session->update(['status' => 'finished']);
            $result = $finalizer->finalize($session);
            $this->line("{$session->name}: {$result['created_absent']} siswa ditandai tidak hadir.");
        }

        return self::SUCCESS;
    }
}
