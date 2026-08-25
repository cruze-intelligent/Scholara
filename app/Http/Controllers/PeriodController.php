<?php

namespace App\Http\Controllers;

use App\Models\Period;
use App\Models\Student;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Weekly timetable — a school-wide gap the parity audit flagged: teacher_subject_assignments
 * says *what* a teacher teaches, never *when*. A Period is one weekly slot for one assignment;
 * overlap is checked for the same teacher, the same class, and the same room, so double-booking
 * isn't possible.
 */
class PeriodController extends Controller
{
    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Period::with(['assignment.teacher', 'assignment.subject', 'assignment.schoolClass']);

        if ($user->hasRole('teacher')) {
            $assignmentIds = $user->teacherSubjectAssignments()->pluck('id');
            $query->whereIn('teacher_subject_assignment_id', $assignmentIds);
        } elseif ($user->hasRole('parent')) {
            $classIds = $user->guardian?->students()->pluck('school_class_id') ?? collect();
            $query->whereHas('assignment', fn ($q) => $q->whereIn('school_class_id', $classIds));
        } elseif ($user->hasRole('learner')) {
            $classId = Student::where('user_id', $user->id)->value('school_class_id');
            $query->whereHas('assignment', fn ($q) => $q->where('school_class_id', $classId));
        }

        $periods = $query->get()->sortBy('start_time')->groupBy('day_of_week');

        return view('periods.index', ['periods' => $periods, 'days' => self::DAYS]);
    }

    public function create(Request $request): View
    {
        $assignments = TeacherSubjectAssignment::whereHas('teacher', fn ($q) => $q->where('school_id', $request->user()->school_id))
            ->with(['teacher', 'subject', 'schoolClass'])
            ->get();

        return view('periods.create', ['assignments' => $assignments, 'days' => self::DAYS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_subject_assignment_id' => ['required', 'exists:teacher_subject_assignments,id'],
            'day_of_week' => ['required', 'in:'.implode(',', self::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:100'],
        ]);

        $assignment = TeacherSubjectAssignment::findOrFail($validated['teacher_subject_assignment_id']);

        $this->assertNoClash($validated, $assignment);

        Period::create([
            ...$validated,
            'school_id' => $request->user()->school_id,
        ]);

        return redirect()->route('periods.index')->with('status', 'Period scheduled.');
    }

    public function destroy(Period $period): RedirectResponse
    {
        $period->delete();

        return redirect()->route('periods.index')->with('status', 'Period removed.');
    }

    /**
     * Same teacher, same class, or same (non-empty) room can't have two overlapping periods on
     * the same day — the actual "room-clash detection" the audit named.
     */
    private function assertNoClash(array $validated, TeacherSubjectAssignment $assignment): void
    {
        $overlapping = Period::where('day_of_week', $validated['day_of_week'])
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->whereHas('assignment', function ($q) use ($assignment) {
                $q->where('teacher_id', $assignment->teacher_id)
                    ->orWhere('school_class_id', $assignment->school_class_id);
            })
            ->exists();

        abort_if($overlapping, 422, 'This clashes with an existing period for this teacher or class.');

        if (! empty($validated['room'])) {
            $roomClash = Period::where('day_of_week', $validated['day_of_week'])
                ->where('room', $validated['room'])
                ->where('start_time', '<', $validated['end_time'])
                ->where('end_time', '>', $validated['start_time'])
                ->exists();

            abort_if($roomClash, 422, 'This room is already booked for an overlapping time.');
        }
    }
}
