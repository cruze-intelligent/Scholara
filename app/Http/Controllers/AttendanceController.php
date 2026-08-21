<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function create(Request $request): View
    {
        $classes = SchoolClass::where('teacher_id', $request->user()->id)->get();
        $classId = $request->integer('class_id') ?: $classes->first()?->id;
        $date = $request->filled('date') ? Carbon::parse($request->string('date')) : today();

        $class = $classId
            ? SchoolClass::with(['students' => fn ($q) => $q->orderBy('first_name')])->find($classId)
            : null;

        $existing = $class
            ? AttendanceRecord::where('school_class_id', $class->id)->whereDate('date', $date)->get()->keyBy('student_id')
            : collect();

        return view('attendance.create', [
            'classes' => $classes,
            'class' => $class,
            'date' => $date,
            'existing' => $existing,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'array'],
            'status.*' => ['in:present,absent,late'],
        ]);

        $class = SchoolClass::findOrFail($validated['school_class_id']);
        abort_unless($class->teacher_id === $request->user()->id, 403);

        foreach ($validated['status'] as $studentId => $status) {
            AttendanceRecord::updateOrCreate(
                ['school_class_id' => $class->id, 'student_id' => $studentId, 'date' => $validated['date']],
                ['status' => $status, 'recorded_by' => $request->user()->id]
            );
        }

        return redirect()->route('attendance.create', ['class_id' => $class->id, 'date' => $validated['date']])
            ->with('status', 'Attendance saved.');
    }

    public function stats(Request $request): View
    {
        $classes = SchoolClass::query()
            ->when($request->user()->hasRole('teacher'), fn ($q) => $q->where('teacher_id', $request->user()->id))
            ->get();

        $classId = $request->integer('class_id') ?: $classes->first()?->id;
        $stats = [];

        if ($classId) {
            $records = AttendanceRecord::where('school_class_id', $classId)->with('student')->get();

            foreach (['male', 'female'] as $gender) {
                $genderRecords = $records->filter(fn ($record) => $record->student?->gender === $gender);
                $total = $genderRecords->count();
                $present = $genderRecords->where('status', 'present')->count();

                $stats[$gender] = [
                    'total' => $total,
                    'present' => $present,
                    'rate' => $total > 0 ? round($present / $total * 100, 1) : null,
                ];
            }
        }

        return view('attendance.stats', compact('classes', 'classId', 'stats'));
    }
}
