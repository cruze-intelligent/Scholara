<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HealthRecordController extends Controller
{
    public function edit(Student $student): View
    {
        $healthRecord = $student->healthRecord ?? new HealthRecord(['student_id' => $student->id]);

        return view('health-records.edit', compact('student', 'healthRecord'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'chronic_conditions' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'vaccinations' => ['nullable', 'string'],
            'family_physician' => ['nullable', 'string'],
        ]);

        $toList = fn (?string $text) => $text
            ? array_values(array_filter(array_map('trim', explode(',', $text))))
            : [];

        HealthRecord::updateOrCreate(
            ['student_id' => $student->id],
            [
                'chronic_conditions' => $toList($validated['chronic_conditions'] ?? null),
                'allergies' => $toList($validated['allergies'] ?? null),
                'vaccinations' => $toList($validated['vaccinations'] ?? null),
                'family_physician' => $validated['family_physician'] ? ['name' => $validated['family_physician']] : null,
            ]
        );

        return redirect()->route('health-records.edit', $student)->with('status', 'Health record updated.');
    }
}
