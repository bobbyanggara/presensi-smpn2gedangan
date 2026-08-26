<?php
namespace App\Services;
use App\Models\Absensi;
use App\Models\AttendanceSession;
use App\Models\Siswa;
class AttendanceSessionFinalizer
{
    public function finalize(AttendanceSession $session): array
    {
        if (!$session->location?->is_required) return ['created_absent'=>0,'skipped'=>true];
        $alreadyScanned=Absensi::where('session_id',$session->id)->pluck('siswa_id');
        $query=Siswa::where('status','active')->whereNotIn('id',$alreadyScanned);
        $created=0;
        $query->chunkById(200,function($students)use($session,&$created){
            foreach($students as $student){
                $record=Absensi::firstOrCreate(['siswa_id'=>$student->id,'session_id'=>$session->id],[
                    'location_id'=>$session->location_id,'tanggal'=>$session->date,'scan_time'=>null,'jam_masuk'=>null,'status'=>'absent','scanner_id'=>null,
                ]);
                if($record->wasRecentlyCreated)$created++;
            }
        });
        return ['created_absent'=>$created,'skipped'=>false];
    }
}
