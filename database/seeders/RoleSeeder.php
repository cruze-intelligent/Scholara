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
        'admin',
        'teacher',
        'parent',
        'learner',
        'nurse',
        'hr',
        'bursar',
        'librarian',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role);
        }
    }
}
