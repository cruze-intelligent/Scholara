<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Roles from docs/ARCHITECTURE.md's role model.
     */
    public const ROLES = [
        // Platform operator — not tied to any school (school_id null); see SuperAdminController.
        'super_admin',
        'admin',
        'teacher',
        // Distinction tags — layered on top of a base functional role (a class teacher is
        // always also 'teacher') rather than replacing it, so every existing hasRole('teacher')-
        // style check keeps working unchanged, and admins can toggle these independently as
        // assignments change. See UserController::DISTINCTION_TAGS.
        'class_teacher',
        'head_of_department',
        'parent',
        'learner',
        'nurse',
        'head_nurse',
        'hr',
        'hr_manager',
        'bursar',
        'head_bursar',
        'librarian',
        'head_librarian',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role);
        }
    }
}
