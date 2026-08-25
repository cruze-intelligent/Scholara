<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Notice;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A learner's own full-depth views — the dashboard only ever showed a five-item summary of each
 * of these; this is the "see everything, not just the highlights" surface for their own records.
 */
class LearnerController extends Controller
{
    public function assessments(Request $request): View
    {
        $student = $this->student($request);

        $scores = $student
            ? $student->assessmentScores()->with('assessment.subject')->latest('recorded_at')->get()
            : collect();

        return view('learner.assessments', compact('scores', 'student'));
    }

    public function attendance(Request $request): View
    {
        $student = $this->student($request);

        $records = $student
            ? $student->attendanceRecords()->latest('date')->paginate(30)
            : AttendanceRecord::whereRaw('0 = 1')->paginate(30);

        return view('learner.attendance', compact('records'));
    }

    public function notices(Request $request): View
    {
        $notices = Notice::where('school_id', $request->user()->school_id)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->paginate(20);

        return view('learner.notices', compact('notices'));
    }

    private function student(Request $request): ?Student
    {
        return Student::where('user_id', $request->user()->id)->first();
    }
}
