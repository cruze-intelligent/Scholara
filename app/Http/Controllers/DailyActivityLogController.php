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
            'meals' => ! empty($validated['meals']) ? array_map('trim', explode(',', $validated['meals'])) : [],
            'bathroom_breaks' => $validated['bathroom_breaks'] ?? 0,
            'sleep_checks' => ! empty($validated['sleep_checks']) ? array_map('trim', explode(',', $validated['sleep_checks'])) : [],
            'nappy_changes' => [],
            'logged_by' => $request->user()->id,
        ]);

        return redirect()->route('daily-activity-logs.index')->with('status', 'Activity log saved.');
    }

    public function edit(Request $request, DailyActivityLog $daily_activity_log): View
    {
        $this->authorizeSameDay($request, $daily_activity_log);

        return view('daily-activity-logs.edit', ['log' => $daily_activity_log]);
    }

    public function update(Request $request, DailyActivityLog $daily_activity_log): RedirectResponse
    {
        $this->authorizeSameDay($request, $daily_activity_log);

        $validated = $request->validate([
            'meals' => ['nullable', 'string'],
            'bathroom_breaks' => ['nullable', 'integer', 'min:0'],
            'sleep_checks' => ['nullable', 'string'],
        ]);

        $daily_activity_log->update([
            'meals' => ! empty($validated['meals']) ? array_map('trim', explode(',', $validated['meals'])) : [],
            'bathroom_breaks' => $validated['bathroom_breaks'] ?? 0,
            'sleep_checks' => ! empty($validated['sleep_checks']) ? array_map('trim', explode(',', $validated['sleep_checks'])) : [],
        ]);

        return redirect()->route('daily-activity-logs.index')->with('status', 'Activity log updated.');
    }

    public function destroy(Request $request, DailyActivityLog $daily_activity_log): RedirectResponse
    {
        $this->authorizeSameDay($request, $daily_activity_log);

        $daily_activity_log->delete();

        return redirect()->route('daily-activity-logs.index')->with('status', 'Activity log deleted.');
    }

    private function authorizeSameDay(Request $request, DailyActivityLog $log): void
    {
        abort_unless($log->logged_by === $request->user()->id || $request->user()->hasRole('admin'), 403);
        abort_unless($log->created_at->isToday() || $request->user()->hasRole('admin'), 422, 'Only entries logged today can still be edited.');
    }
}
