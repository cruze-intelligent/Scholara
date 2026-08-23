<?php

namespace App\Http\Controllers;

use App\Models\ClinicVisit;
use App\Models\Student;
use App\Notifications\ClinicVisitLogged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClinicVisitController extends Controller
{
    public function index(Request $request): View
    {
        // ClinicVisit has no school_id of its own (no BelongsToSchool scope), so this has to
        // filter through the student relation explicitly or it leaks other schools' records.
        $visits = ClinicVisit::with(['student', 'loggedBy'])
            ->whereHas('student', fn ($q) => $q->where('school_id', $request->user()->school_id))
            ->latest('occurred_at')
            ->paginate(20);

        return view('clinic-visits.index', compact('visits'));
    }

    public function create(): View
    {
        $students = Student::orderBy('first_name')->get();

        return view('clinic-visits.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('school_id', $request->user()->school_id),
            ],
            'reason' => ['required', 'string', 'max:255'],
            'diagnosis' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'outcome' => ['required', 'in:returned_to_class,sick_bay,referred_to_hospital'],
        ]);

        $visit = ClinicVisit::create([
            ...$validated,
            'logged_by' => $request->user()->id,
            'occurred_at' => now(),
        ]);

        $visit->student->guardians->each(
            fn ($guardian) => $guardian->user?->notify(new ClinicVisitLogged($visit))
        );

        return redirect()->route('clinic-visits.index')->with('status', 'Clinic visit logged.');
    }
}
