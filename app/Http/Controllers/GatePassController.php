<?php

namespace App\Http\Controllers;

use App\Models\GatePass;
use App\Models\Student;
use App\Notifications\GatePassStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gate passes — request (guardian/learner-initiated or teacher-initiated), approve (admin/
 * teacher), then log the actual departure/return. Role scoping mirrors IncidentReportController:
 * a guardian sees only their own children's passes, a learner only their own.
 */
class GatePassController extends Controller
{
    // Who can see/manage gate passes for students in general — a student leaving campus is
    // admin/teacher/nurse territory, not HR/bursar/librarian's. Parent/learner are handled
    // separately below since they're always scoped to their own child/self regardless.
    private const STAFF_ROLES = ['admin', 'teacher', 'nurse'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = GatePass::with(['student', 'requestedBy', 'approvedBy'])->latest();

        if ($user->hasRole('parent')) {
            $studentIds = $user->guardian?->students()->pluck('students.id') ?? collect();
            $query->whereIn('student_id', $studentIds);
        } elseif ($user->hasRole('learner')) {
            $query->where('student_id', Student::where('user_id', $user->id)->value('id'));
        } else {
            abort_unless($user->hasAnyRole(self::STAFF_ROLES), 403);
        }

        return view('gate-passes.index', ['gatePasses' => $query->paginate(15)]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole('parent')) {
            $students = $user->guardian?->students ?? collect();
        } elseif ($user->hasRole('learner')) {
            $students = Student::where('user_id', $user->id)->get();
        } else {
            abort_unless($user->hasAnyRole(self::STAFF_ROLES), 403);
            $students = Student::orderBy('first_name')->get();
        }

        return view('gate-passes.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if ($user->hasRole('parent')) {
            $allowed = $user->guardian?->students()->where('students.id', $validated['student_id'])->exists() ?? false;
            abort_unless($allowed, 403);
        } elseif ($user->hasRole('learner')) {
            $allowed = Student::where('user_id', $user->id)->where('id', $validated['student_id'])->exists();
            abort_unless($allowed, 403);
        } else {
            abort_unless($user->hasAnyRole(self::STAFF_ROLES), 403);
        }

        GatePass::create([
            'student_id' => $validated['student_id'],
            'requested_by' => $user->id,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('gate-passes.index')->with('status', 'Gate pass requested.');
    }

    public function approve(Request $request, GatePass $gatePass): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', 'in:approved,rejected']]);

        abort_unless($gatePass->status === 'pending', 422, 'This request has already been decided.');

        $gatePass->update([
            'status' => $validated['status'],
            'approved_by' => $request->user()->id,
        ]);

        $gatePass->requestedBy?->notify(new GatePassStatusUpdated($gatePass));

        return back()->with('status', 'Gate pass '.$validated['status'].'.');
    }

    public function depart(Request $request, GatePass $gatePass): RedirectResponse
    {
        abort_unless($gatePass->status === 'approved', 422, 'Only approved gate passes can be logged as departed.');
        abort_if($gatePass->departed_at, 422, 'Departure already logged.');

        $gatePass->update(['departed_at' => now()]);

        return back()->with('status', 'Departure logged.');
    }

    public function returned(Request $request, GatePass $gatePass): RedirectResponse
    {
        abort_unless($gatePass->departed_at, 422, 'This student has not departed yet.');
        abort_if($gatePass->returned_at, 422, 'Return already logged.');

        $gatePass->update(['returned_at' => now()]);

        return back()->with('status', 'Return logged.');
    }
}
