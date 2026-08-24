<?php

namespace App\Http\Controllers;

use App\Models\MedicationAdministration;
use App\Models\Student;
use App\Notifications\MedicationAdministered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicationAdministrationController extends Controller
{
    public function index(Request $request): View
    {
        // MedicationAdministration has no school_id of its own (no BelongsToSchool scope), so
        // this has to filter through the student relation explicitly or it leaks other schools'
        // records into the list.
        $administrations = MedicationAdministration::with(['student', 'administeredBy'])
            ->whereHas('student', fn ($q) => $q->where('school_id', $request->user()->school_id))
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
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('school_id', $request->user()->school_id),
            ],
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

    public function edit(Request $request, MedicationAdministration $medication): View
    {
        $this->authorizeEdit($request, $medication);

        $students = Student::orderBy('first_name')->get();

        return view('medications.edit', ['administration' => $medication, 'students' => $students]);
    }

    public function update(Request $request, MedicationAdministration $medication): RedirectResponse
    {
        $this->authorizeEdit($request, $medication);

        $validated = $request->validate([
            'medication_name' => ['required', 'string', 'max:255'],
            'dose' => ['required', 'string', 'max:100'],
            'route' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $medication->update($validated);

        return redirect()->route('medications.index')->with('status', 'Record updated.');
    }

    public function destroy(Request $request, MedicationAdministration $medication): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $medication->delete();

        return redirect()->route('medications.index')->with('status', 'Record deleted.');
    }

    private function authorizeEdit(Request $request, MedicationAdministration $medication): void
    {
        abort_unless(
            $medication->administered_by === $request->user()->id || $request->user()->hasRole('admin'),
            403
        );
    }
}
