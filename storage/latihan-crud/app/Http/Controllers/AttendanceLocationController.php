<?php
namespace App\Http\Controllers;
use App\Models\AttendanceLocation;
use Illuminate\Http\Request;
class AttendanceLocationController extends Controller
{
    public function index() { return response()->json(AttendanceLocation::orderBy('name')->get()); }
    public function store(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:100|unique:attendance_locations,name','is_required'=>'sometimes|boolean','description'=>'nullable|string','status'=>'sometimes|boolean']);
        return response()->json(AttendanceLocation::create($data), 201);
    }
}
