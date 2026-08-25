<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin-only bulk student list handling — export the current roster, or import a batch of new
 * students from a CSV rather than adding them one at a time via UserController. Deliberately
 * students-only (no guardian columns) since a bare, unlinked Student is already a valid state in
 * this app (see UserController's unlinkedStudents flow) — an admin links a guardian afterwards
 * the same way they would for a manually-created student.
 */
class StudentCsvController extends Controller
{
    private const HEADER = ['admission_no', 'first_name', 'last_name', 'dob', 'gender', 'curriculum_level', 'school_class'];

    public function export(Request $request): StreamedResponse
    {
        $students = Student::with('schoolClass')
            ->where('school_id', $request->user()->school_id)
            ->orderBy('first_name')
            ->get();

        return response()->streamDownload(function () use ($students) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::HEADER);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->admission_no,
                    $student->first_name,
                    $student->last_name,
                    optional($student->dob)->toDateString(),
                    $student->gender,
                    $student->curriculum_level,
                    $student->schoolClass?->name,
                ]);
            }

            fclose($handle);
        }, 'students-'.now()->format('Y-m-d').'.csv');
    }

    public function importCreate(): View
    {
        return view('students.import', ['header' => self::HEADER]);
    }

    public function importStore(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map('trim', fgetcsv($handle) ?: []);

        $classesByName = SchoolClass::where('school_id', $request->user()->school_id)
            ->get()->keyBy(fn ($c) => Str::lower($c->name));

        $created = 0;
        $errors = [];
        $row = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            $record = array_combine($header, array_pad($data, count($header), null));

            if (empty($record['first_name']) || empty($record['curriculum_level'])) {
                $errors[] = "Row {$row}: first_name and curriculum_level are required.";

                continue;
            }

            if (! in_array($record['curriculum_level'], ['nursery', 'primary', 'lower_secondary', 'upper_secondary'], true)) {
                $errors[] = "Row {$row}: invalid curriculum_level '{$record['curriculum_level']}'.";

                continue;
            }

            Student::create([
                'school_id' => $request->user()->school_id,
                'school_class_id' => $classesByName->get(Str::lower($record['school_class'] ?? ''))?->id,
                'admission_no' => $record['admission_no'] ?: 'ADM-'.Str::upper(Str::random(6)),
                'first_name' => $record['first_name'],
                'last_name' => $record['last_name'] ?? '',
                'dob' => $record['dob'] ?: null,
                'gender' => in_array($record['gender'] ?? null, ['male', 'female'], true) ? $record['gender'] : 'male',
                'curriculum_level' => $record['curriculum_level'],
            ]);
            $created++;
        }

        fclose($handle);

        return redirect()->route('users.index')->with([
            'status' => "Imported {$created} student(s).".($errors ? ' '.count($errors).' row(s) skipped.' : ''),
            'importErrors' => $errors,
        ]);
    }
}
