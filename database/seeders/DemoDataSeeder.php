<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\AttendanceRecord;
use App\Models\Guardian;
use App\Models\InventoryItem;
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

            $user->syncRoles([$role]);

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

        // A few Maths assessments with scores so the predictive-analytics
        // and marksheet screens have a real trend to show.
        $grading = new GradingService;
        $scores = ['AoI' => [78, 65], 'MOT' => [70, 60], 'EOT' => [82, 55]];

        foreach ($scores as $type => [$demoScore, $classmateScore]) {
            $assessment = Assessment::firstOrCreate(
                ['school_id' => $school->id, 'subject_id' => $math->id, 'school_class_id' => $class->id, 'type' => $type, 'term' => 'Term 2 2026'],
                ['max_score' => 100, 'weight' => 1]
            );

            foreach ([$student->id => $demoScore, $classmate->id => $classmateScore] as $studentId => $raw) {
                AssessmentScore::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'student_id' => $studentId],
                    [
                        'raw_score' => $raw,
                        'scaled_score' => $grading->scaleScore($raw, (float) $assessment->max_score),
                        'recorded_by' => $teacherUser->id,
                        'recorded_at' => now(),
                    ]
                );
            }
        }

        // Attendance for the last 5 school days, both students.
        foreach (range(0, 4) as $daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();

            AttendanceRecord::updateOrCreate(
                ['school_class_id' => $class->id, 'student_id' => $student->id, 'date' => $date],
                ['status' => 'present', 'recorded_by' => $teacherUser->id]
            );
            AttendanceRecord::updateOrCreate(
                ['school_class_id' => $class->id, 'student_id' => $classmate->id, 'date' => $date],
                ['status' => $daysAgo === 0 ? 'absent' : 'present', 'recorded_by' => $teacherUser->id]
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
    }
}
