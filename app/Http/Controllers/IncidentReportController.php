<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\Student;
use App\Notifications\IncidentStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = IncidentReport::with(['student', 'reporter', 'assignedTo'])->latest();

        if ($user->hasRole('parent')) {
            $studentIds = $user->guardian?->students()->pluck('students.id') ?? collect();
            $query->whereIn('student_id', $studentIds);
        } elseif ($user->hasRole('learner')) {
            $query->where('student_id', Student::where('user_id', $user->id)->value('id'));
        }

        return view('incidents.index', ['incidents' => $query->paginate(15)]);
    }

    public function create(Request $request): View
    {
        $students = $request->user()->hasAnyRole(['admin', 'teacher', 'nurse'])
            ? Student::orderBy('first_name')->get()
            : collect();

        return view('incidents.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['nullable', 'exists:students,id'],
            'category' => ['required', 'in:bullying,violence,other'],
            'description' => ['required', 'string'],
        ]);

        $anonymous = $request->boolean('anonymous');

        IncidentReport::create([
            'reporter_id' => $anonymous ? null : $request->user()->id,
            'anonymous' => $anonymous,
            'student_id' => $validated['student_id'] ?? null,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'status' => 'open',
        ]);

        return redirect()->route('incidents.index')->with('status', 'Report submitted.');
    }

    public function updateStatus(Request $request, IncidentReport $incident): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_review,resolved'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $incident->update($validated);

        // Anonymous reports have no reporter_id to notify — that's by design (RTRR-aligned,
        // see docs/DECISIONS.md), not a gap.
        $incident->reporter?->notify(new IncidentStatusUpdated($incident));

        return back()->with('status', 'Report updated.');
    }
}
