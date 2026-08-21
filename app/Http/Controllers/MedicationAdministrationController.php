<?php

namespace App\Http\Controllers;

use App\Models\MedicationAdministration;
use App\Models\Student;
use App\Notifications\MedicationAdministered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicationAdministrationController extends Controller
{
    public function index(): View
    {
        $administrations = MedicationAdministration::with(['student', 'administeredBy'])
            ->latest('administered_at')
            ->paginate(20);

        return view('medications.index', compact('administrations'));
    }

    public function create(): View
    {
        $students = Student::orderBy('first_name')->get();

        return view('medications.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'medication_name' => ['required', 'string', 'max:255'],
            'dose' => ['required', 'string', 'max:100'],
            'route' => ['required', 'string', 'max:100'],
            'scheduled_time' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'checked_right_patient' => ['nullable', 'boolean'],
            'checked_right_drug' => ['nullable', 'boolean'],
            'checked_right_dose' => ['nullable', 'boolean'],
            'checked_right_route' => ['nullable', 'boolean'],
            'checked_right_time' => ['nullable', 'boolean'],
        ]);

        foreach (array_keys(MedicationAdministration::RIGHTS) as $field) {
            $validated[$field] = $request->boolean($field);
        }

        $administration = MedicationAdministration::create([
            ...$validated,
            'administered_by' => $request->user()->id,
            'administered_at' => now(),
        ]);

        $administration->student->guardians->each(
            fn ($guardian) => $guardian->user?->notify(new MedicationAdministered($administration))
        );

        return redirect()->route('medications.index')->with('status', 'Medication administration logged.');
    }
}
