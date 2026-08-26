<?php
namespace App\Http\Controllers;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
class AttendanceSessionController extends Controller
{
    public function index(Request $request) { return response()->json(AttendanceSession::with('location')->when($request->filled('date'), fn($q)=>$q->whereDate('date',$request->date))->latest('date')->latest('start_time')->paginate(50)); }
    public function store(Request $request)
    {
        $data=$request->validate(['location_id'=>'required|exists:attendance_locations,id','name'=>'required|string|max:100','date'=>'required|date','start_time'=>'required|date_format:H:i','end_time'=>'required|date_format:H:i|after:start_time','status'=>'nullable|in:scheduled,active,finished']);
        $data['status']=$data['status']??'scheduled';
        return response()->json(AttendanceSession::create($data)->load('location'),201);
    }
    public function activate(AttendanceSession $session) { $session->update(['status'=>'active']); return response()->json($session->fresh('location')); }
    public function finish(AttendanceSession $session) { $session->update(['status'=>'finished']); $result=app(\App\Services\AttendanceSessionFinalizer::class)->finalize($session); return response()->json(['session'=>$session->fresh('location'),'result'=>$result]); }
}
