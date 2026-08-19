<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One demo school with one user per role, for local development only —
 * lets every dashboard be clicked through without hand-creating records.
 * Not intended for production seeding.
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
                    ]
                );
            }
        }

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

        $guardian = Guardian::firstOrCreate(
            ['user_id' => $parentUser->id],
            ['relationship_to_student' => 'Parent']
        );

        $student->guardians()->syncWithoutDetaching([$guardian->id]);
    }
}
