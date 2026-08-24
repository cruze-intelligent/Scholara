<?php

namespace App\Http\Controllers;

use App\Models\MilestoneChecklist;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MilestoneChecklistController extends Controller
{
    /**
     * No milestone catalog table exists in the schema (see docs/DATA_MODEL.md);
     * this static list is the documented default a real catalog would replace.
     */
    public const CATALOG = [
        'physical' => ['Walks unaided', 'Runs confidently', 'Climbs stairs one foot per step', 'Holds a pencil with a tripod grip'],
        'cognitive' => ['Sorts objects by colour', 'Counts to ten', 'Names basic shapes', 'Completes a simple puzzle'],
        'emotional' => ['Separates from caregiver without distress', 'Shares toys with peers', 'Names own emotions', 'Follows two-step instructions'],
        'health' => ['Up to date on scheduled vaccinations', 'Independent toileting', 'Washes hands unprompted', 'Sleeps through nap time'],
    ];

    public function index(): View
    {
        $checklists = MilestoneChecklist::with('student')->latest('achieved_at')->paginate(20);

        return view('milestones.index', compact('checklists'));
    }

    public function create(): View
    {
        $students = Student::where('curriculum_level', 'nursery')->orderBy('first_name')->get();

        return view('milestones.create', ['students' => $students, 'catalog' => self::CATALOG]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'domain' => ['required', 'in:physical,cognitive,emotional,health'],
            'milestone_label' => ['required', 'string', 'max:255'],
            'achieved_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        MilestoneChecklist::create($validated);

        return redirect()->route('milestones.index')->with('status', 'Milestone recorded.');
    }

    public function edit(Request $request, MilestoneChecklist $milestone): View
    {
        $this->authorizeSameDay($request, $milestone);

        return view('milestones.edit', ['checklist' => $milestone, 'catalog' => self::CATALOG]);
    }

    public function update(Request $request, MilestoneChecklist $milestone): RedirectResponse
    {
        $this->authorizeSameDay($request, $milestone);

        $validated = $request->validate([
            'domain' => ['required', 'in:physical,cognitive,emotional,health'],
            'milestone_label' => ['required', 'string', 'max:255'],
            'achieved_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $milestone->update($validated);

        return redirect()->route('milestones.index')->with('status', 'Milestone updated.');
    }

    public function destroy(Request $request, MilestoneChecklist $milestone): RedirectResponse
    {
        $this->authorizeSameDay($request, $milestone);

        $milestone->delete();

        return redirect()->route('milestones.index')->with('status', 'Milestone deleted.');
    }

    /**
     * MilestoneChecklist has no author column to check ownership against, unlike the other two
     * nursery modules — same-day + the same role gate as create() is the honest scope here.
     */
    private function authorizeSameDay(Request $request, MilestoneChecklist $milestone): void
    {
        abort_unless($request->user()->hasAnyRole(['teacher', 'nurse', 'admin']), 403);
        abort_unless($milestone->created_at->isToday() || $request->user()->hasRole('admin'), 422, 'Only milestones recorded today can still be edited.');
    }
}
