<?php

namespace App\Http\Controllers;

use App\Models\DailyActivityLog;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyActivityLogController extends Controller
{
    public function index(): View
    {
        $logs = DailyActivityLog::with(['student', 'loggedBy'])->latest('date')->paginate(20);

        return view('daily-activity-logs.index', compact('logs'));
    }

    public function create(): View
    {
        $students = Student::where('curriculum_level', 'nursery')->orderBy('first_name')->get();

        return view('daily-activity-logs.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'date' => ['required', 'date'],
            'meals' => ['nullable', 'string'],
            'bathroom_breaks' => ['nullable', 'integer', 'min:0'],
            'sleep_checks' => ['nullable', 'string'],
        ]);

        DailyActivityLog::create([
            'student_id' => $validated['student_id'],
            'date' => $validated['date'],
            'meals' => $validated['meals'] ? array_map('trim', explode(',', $validated['meals'])) : [],
            'bathroom_breaks' => $validated['bathroom_breaks'] ?? 0,
            'sleep_checks' => $validated['sleep_checks'] ? array_map('trim', explode(',', $validated['sleep_checks'])) : [],
            'nappy_changes' => [],
            'logged_by' => $request->user()->id,
        ]);

        return redirect()->route('daily-activity-logs.index')->with('status', 'Activity log saved.');
    }
}
