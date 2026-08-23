<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Aggregate trend reports, not single-record views — see docs/HARDENING_TODO.md Phase 3.5.
 * Every query filters by school explicitly: these join across tables (AssessmentScore has no
 * school_id of its own, ClinicVisit/MedicationAdministration only reach a school via student_id),
 * so none of them benefit from a model's BelongsToSchool global scope the way a plain Eloquent
 * query would.
 */
class ReportController extends Controller
{
    public function academics(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        $bySubjectTerm = DB::table('assessment_scores')
            ->join('assessments', 'assessments.id', '=', 'assessment_scores.assessment_id')
            ->join('subjects', 'subjects.id', '=', 'assessments.subject_id')
            ->where('assessments.school_id', $schoolId)
            ->select(
                'subjects.name as subject',
                'assessments.term as term',
                DB::raw('AVG(assessment_scores.scaled_score) as avg_score'),
                DB::raw('COUNT(DISTINCT assessment_scores.student_id) as student_count'),
            )
            ->groupBy('subjects.name', 'assessments.term')
            ->orderBy('subjects.name')
            ->orderBy('assessments.term')
            ->get()
            ->groupBy('subject');

        $atRisk = DB::table('assessment_scores')
            ->join('assessments', 'assessments.id', '=', 'assessment_scores.assessment_id')
            ->join('students', 'students.id', '=', 'assessment_scores.student_id')
            ->where('assessments.school_id', $schoolId)
            ->select(
                'students.first_name', 'students.last_name',
                DB::raw('AVG(assessment_scores.scaled_score) as avg_score'),
            )
            ->groupBy('assessment_scores.student_id', 'students.first_name', 'students.last_name')
            ->having('avg_score', '<', 60)
            ->orderBy('avg_score')
            ->get();

        return view('reports.academics', compact('bySubjectTerm', 'atRisk'));
    }

    public function health(Request $request): View
    {
        $schoolId = $request->user()->school_id;
        $since = now()->subDays(90);

        $visitsByReason = DB::table('clinic_visits')
            ->join('students', 'students.id', '=', 'clinic_visits.student_id')
            ->where('students.school_id', $schoolId)
            ->where('clinic_visits.occurred_at', '>=', $since)
            ->select('clinic_visits.reason', DB::raw('COUNT(*) as count'))
            ->groupBy('clinic_visits.reason')
            ->orderByDesc('count')
            ->get();

        $visitsByOutcome = DB::table('clinic_visits')
            ->join('students', 'students.id', '=', 'clinic_visits.student_id')
            ->where('students.school_id', $schoolId)
            ->where('clinic_visits.occurred_at', '>=', $since)
            ->select('clinic_visits.outcome', DB::raw('COUNT(*) as count'))
            ->groupBy('clinic_visits.outcome')
            ->orderByDesc('count')
            ->get();

        $medicationsByName = DB::table('medication_administrations')
            ->join('students', 'students.id', '=', 'medication_administrations.student_id')
            ->where('students.school_id', $schoolId)
            ->where('medication_administrations.administered_at', '>=', $since)
            ->select('medication_administrations.medication_name', DB::raw('COUNT(*) as count'))
            ->groupBy('medication_administrations.medication_name')
            ->orderByDesc('count')
            ->get();

        return view('reports.health', compact('visitsByReason', 'visitsByOutcome', 'medicationsByName'));
    }
}
