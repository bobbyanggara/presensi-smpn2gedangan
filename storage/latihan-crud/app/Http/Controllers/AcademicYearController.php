<?php
namespace App\Http\Controllers;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
class AcademicYearController extends Controller
{
    public function index() { return response()->json(AcademicYear::orderByDesc('start_date')->get()); }
    public function store(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:20|unique:academic_years,name','start_date'=>'required|date','end_date'=>'required|date|after:start_date','is_active'=>'sometimes|boolean']);
        if (!empty($data['is_active'])) AcademicYear::query()->update(['is_active'=>false]);
        return response()->json(AcademicYear::create($data), 201);
    }
}
