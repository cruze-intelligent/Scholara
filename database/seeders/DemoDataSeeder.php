<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\AttendanceRecord;
use App\Models\CalendarEvent;
use App\Models\ClinicVisit;
use App\Models\Guardian;
use App\Models\InventoryItem;
use App\Models\MedicationAdministration;
use App\Models\Notice;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Services\Academics\GradingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One demo school with one user per role, for local development only —
 * lets every dashboard/screen be clicked through without hand-creating
 * records. Not intended for production seeding.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Platform operator — no school_id, provisioned here for local dev only. In production
        // this account is created by hand (e.g. via `php artisan tinker`), never through a
        // public form — see RegisteredUserController, which only ever creates school admins.
        $superAdmin = User::firstOrCreate(
            ['email' => 'super-admin@scholara.test'],
            ['name' => 'Demo Super Admin', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $superAdmin->syncRoles(['super_admin']);

        $school = School::firstOrCreate(
            ['subdomain' => 'demo'],
            ['name' => 'Scholara Demo School', 'address' => 'Kampala, Uganda']
        );

        $class = SchoolClass::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Primary 5'],
            ['level' => 'primary']
        );

        $nurseryClass = SchoolClass::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Nursery A'],
            ['level' => 'nursery']
        );

        $users = [
            'admin' => ['name' => 'Demo Admin', 'email' => 'admin@scholara.test'],
            'teacher' => ['name' => 'Demo Teacher', 'email' => 'teacher@scholara.test'],
            'parent' => ['name' => 'Demo Parent', 'email' => 'parent@scholara.test'],
            'learner' => ['name' => 'Demo Learner', 'email' => 'learner@scholara.test'],
            'nurse' => ['name' => 'Demo Nurse', 'email' => 'nurse@scholara.test'],
            'hr' => ['name' => 'Demo HR Officer', 'email' => 'hr@scholara.test'],
            'bursar' => ['name' => 'Demo Bursar', 'email' => 'bursar@scholara.test'],
            'librarian' => ['name' => 'Demo Librarian', 'email' => 'librarian@scholara.test'],
        ];

        $salaries = [
            'teacher' => 900_000,
            'nurse' => 850_000,
            'hr' => 1_100_000,
            'bursar' => 1_000_000,
            'librarian' => 700_000,
        ];

        foreach ($users as $role => $attrs) {
            $user = User::firstOrCreate(
                ['email' => $attrs['email']],
                [
                    'name' => $attrs['name'],
                    'school_id' => $school->id,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // The demo teacher is also the homeroom (class) teacher for Primary 5 below, so
            // they hold both roles — demonstrates the class_teacher distinction end to end.
            $user->syncRoles($role === 'teacher' ? [$role, 'class_teacher'] : [$role]);

            if ($role === 'teacher') {
                $class->update(['teacher_id' => $user->id]);
            }

            if (in_array($role, ['teacher', 'nurse', 'hr', 'bursar', 'librarian'], true)) {
                StaffProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'trn' => $role === 'teacher' ? '1234567' : null,
                        'role_title' => ucfirst($role),
                        'hire_date' => now()->subYear(),
                        'monthly_gross_salary' => $salaries[$role],
                    ]
                );
            }
        }

        $teacherUser = User::where('email', 'teacher@scholara.test')->first();
        $learnerUser = User::where('email', 'learner@scholara.test')->first();
        $parentUser = User::where('email', 'parent@scholara.test')->first();

        $student = Student::firstOrCreate(
            ['admission_no' => 'DEMO-0001'],
            [
                'school_id' => $school->id,
                'user_id' => $learnerUser->id,
                'school_class_id' => $class->id,
                'first_name' => 'Demo',
                'last_name' => 'Learner',
                'dob' => now()->subYears(11),
                'gender' => 'female',
                'curriculum_level' => 'primary',
            ]
        );

        // A second, unlinked classmate so class-mean/attendance-stats screens
        // have more than one row to show.
        $classmate = Student::firstOrCreate(
            ['admission_no' => 'DEMO-0002'],
            [
                'school_id' => $school->id,
                'school_class_id' => $class->id,
                'first_name' => 'Classmate',
                'last_name' => 'Two',
                'dob' => now()->subYears(11),
                'gender' => 'male',
                'curriculum_level' => 'primary',
            ]
        );

        // Five more classmates — enough spread (weak/average/strong) for the trend reports
        // (docs/HARDENING_TODO.md Phase 3.5) to show something real rather than two data points.
        $moreClassmates = [];
        foreach ([
            ['Three', 'female', 88],
            ['Four', 'male', 45],
            ['Five', 'female', 70],
            ['Six', 'male', 92],
            ['Seven', 'female', 55],
        ] as $i => [$lastName, $gender, $baseScore]) {
            $moreClassmates[$baseScore] = Student::firstOrCreate(
                ['admission_no' => 'DEMO-'.str_pad((string) (4 + $i), 4, '0', STR_PAD_LEFT)],
                [
                    'school_id' => $school->id,
                    'school_class_id' => $class->id,
                    'first_name' => 'Classmate',
                    'last_name' => $lastName,
                    'dob' => now()->subYears(11),
                    'gender' => $gender,
                    'curriculum_level' => 'primary',
                ]
            );
        }

        $nurseryStudent = Student::firstOrCreate(
            ['admission_no' => 'DEMO-0003'],
            [
                'school_id' => $school->id,
                'school_class_id' => $nurseryClass->id,
                'first_name' => 'Nursery',
                'last_name' => 'Kid',
                'dob' => now()->subYears(3),
                'gender' => 'female',
                'curriculum_level' => 'nursery',
            ]
        );

        $guardian = Guardian::firstOrCreate(
            ['user_id' => $parentUser->id],
            ['relationship_to_student' => 'Parent']
        );

        $student->guardians()->syncWithoutDetaching([$guardian->id]);

        // Subjects + a teaching assignment so the teacher can create
        // assessments/take attendance for this class.
        $math = Subject::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Mathematics'],
            ['curriculum_level' => 'primary']
        );
        $english = Subject::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'English'],
            ['curriculum_level' => 'primary']
        );

        TeacherSubjectAssignment::firstOrCreate([
            'teacher_id' => $teacherUser->id,
            'subject_id' => $math->id,
            'school_class_id' => $class->id,
        ]);
        TeacherSubjectAssignment::firstOrCreate([
            'teacher_id' => $teacherUser->id,
            'subject_id' => $english->id,
            'school_class_id' => $class->id,
        ]);

        // Assessments across two terms and both subjects, for every student in the class — a
        // real spread (weak/average/strong, each trending slightly upward Term 1 -> Term 2) so
        // the academic trend report (docs/HARDENING_TODO.md Phase 3.5) has something real to
        // show rather than two data points. Deterministic (no rand()), so re-seeding is stable.
        $grading = new GradingService;
        $classRoster = [75 => $student, 60 => $classmate, ...$moreClassmates];

        foreach (['Term 1 2026', 'Term 2 2026'] as $term) {
            $termBonus = $term === 'Term 2 2026' ? 6 : 0;

            foreach ([$math, $english] as $subject) {
                foreach (GradingService::TYPE_WEIGHTS as $type => $weight) {
                    $typeAdjust = match ($type) {
                        'AoI' => -5, 'MOT' => -3, default => 0,
                    };

                    $assessment = Assessment::firstOrCreate(
                        ['school_id' => $school->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id, 'type' => $type, 'term' => $term],
                        ['max_score' => 100, 'weight' => 1]
                    );

                    foreach ($classRoster as $baseScore => $rosterStudent) {
                        $raw = max(20, min(100, $baseScore + $termBonus + $typeAdjust));

                        AssessmentScore::updateOrCreate(
                            ['assessment_id' => $assessment->id, 'student_id' => $rosterStudent->id],
                            [
                                'raw_score' => $raw,
                                'scaled_score' => $grading->scaleScore($raw, (float) $assessment->max_score),
                                'recorded_by' => $teacherUser->id,
                                'recorded_at' => now(),
                            ]
                        );
                    }
                }
            }
        }

        // Attendance for the last 15 school days, every student — a few absences/lates spread
        // deterministically by student index so the gender-stats report has a real gap to show.
        $rosterByIndex = array_values($classRoster);

        foreach (range(0, 14) as $daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();

            foreach ($rosterByIndex as $i => $rosterStudent) {
                $status = match (true) {
                    ($daysAgo + $i) % 7 === 0 => 'absent',
                    ($daysAgo + $i) % 5 === 0 => 'late',
                    default => 'present',
                };

                AttendanceRecord::updateOrCreate(
                    ['school_class_id' => $class->id, 'student_id' => $rosterStudent->id, 'date' => $date],
                    ['status' => $status, 'recorded_by' => $teacherUser->id]
                );
            }
        }

        // Clinic visits + medication administrations spread over the last two months, varied
        // reasons/outcomes across several students, so the health trend report
        // (docs/HARDENING_TODO.md Phase 3.5) has something real to show. Fixed times (not
        // `now()`'s current time) keep this idempotent across re-seeds on the same day.
        $nurseUser = User::where('email', 'nurse@scholara.test')->first();
        $healthRoster = [$student, $classmate, $moreClassmates[88], $moreClassmates[45], $nurseryStudent];

        $visits = [
            [0, 'Headache', 'returned_to_class'], [3, 'Stomach ache', 'returned_to_class'],
            [7, 'Fever', 'sick_bay'], [12, 'Minor fall, grazed knee', 'returned_to_class'],
            [18, 'Fever', 'sick_bay'], [25, 'Allergic reaction', 'referred_to_hospital'],
            [34, 'Headache', 'returned_to_class'], [48, 'Stomach ache', 'sick_bay'],
        ];

        foreach ($visits as $i => [$daysAgo, $reason, $outcome]) {
            $visitStudent = $healthRoster[$i % count($healthRoster)];

            ClinicVisit::firstOrCreate(
                ['student_id' => $visitStudent->id, 'reason' => $reason, 'occurred_at' => now()->subDays($daysAgo)->setTime(9, 30)],
                ['outcome' => $outcome, 'logged_by' => $nurseUser->id]
            );
        }

        foreach ([[1, 'Paracetamol', '250mg'], [7, 'Antihistamine', '5ml'], [18, 'Paracetamol', '250mg']] as $i => [$daysAgo, $medication, $dose]) {
            $medStudent = $healthRoster[$i % count($healthRoster)];

            MedicationAdministration::firstOrCreate(
                ['student_id' => $medStudent->id, 'medication_name' => $medication, 'administered_at' => now()->subDays($daysAgo)->setTime(10, 0)],
                [
                    'dose' => $dose, 'route' => 'oral', 'administered_by' => $nurseUser->id,
                    'checked_right_patient' => true, 'checked_right_drug' => true, 'checked_right_dose' => true,
                    'checked_right_route' => true, 'checked_right_time' => true,
                ]
            );
        }

        Notice::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Welcome back for Term 2'],
            [
                'author_id' => $teacherUser->id,
                'audience' => 'all',
                'body' => 'Term 2 begins Monday — please ensure fees and uniforms are ready.',
                'published_at' => now(),
            ]
        );

        InventoryItem::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Exercise books'],
            ['category' => 'library', 'quantity' => 250, 'unit' => 'pieces']
        );
        InventoryItem::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Football'],
            ['category' => 'equipment', 'quantity' => 6, 'unit' => 'pieces']
        );

        // Referenced by nursery screens (daily logs, milestones, WOW moments).
        $nurseryStudent->guardians()->syncWithoutDetaching([$guardian->id]);

        $adminUser = User::where('email', 'admin@scholara.test')->first();

        CalendarEvent::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Term 2 begins'],
            ['created_by' => $adminUser->id, 'category' => 'term_start', 'start_date' => now()->startOfMonth()]
        );
        CalendarEvent::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Mid-term break'],
            [
                'created_by' => $adminUser->id,
                'category' => 'holiday',
                'start_date' => now()->addWeeks(6),
                'end_date' => now()->addWeeks(6)->addDays(4),
            ]
        );
        CalendarEvent::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'End-of-term exams'],
            [
                'created_by' => $adminUser->id,
                'category' => 'exam_period',
                'start_date' => now()->addWeeks(11),
                'end_date' => now()->addWeeks(11)->addDays(4),
            ]
        );
    }
}
