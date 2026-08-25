<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin-only account management — see docs/HARDENING_TODO.md Phase 1. Every action is scoped to
 * the acting admin's own school; there's no cross-school user management by design.
 */
class UserController extends Controller
{
    private const STAFF_ROLES = ['teacher', 'nurse', 'hr', 'bursar', 'librarian'];

    /** Base functional roles offered in the "Role" dropdown — 'parent' is excluded since those
     *  accounts are auto-provisioned from student enrollment (see StudentController), but stays
     *  valid to submit so an existing parent account can still be edited through this controller. */
    private const BASE_ROLES = ['admin', 'teacher', 'nurse', 'hr', 'bursar', 'librarian', 'learner'];

    /**
     * Distinction tags — layered on top of a base staff role rather than replacing it, so an
     * admin can flip one on or off independently as assignments change (this year's class
     * teacher isn't necessarily next year's) without recreating the account. Keyed by the base
     * role they apply to, so the form only offers the tags relevant to whichever role is picked.
     * Each tag is itself a plain Spatie role — StudentController checks hasRole() against these
     * names directly to gate the extra access that comes with the distinction.
     */
    private const DISTINCTION_TAGS = [
        'teacher' => ['class_teacher' => 'Class Teacher', 'head_of_department' => 'Head of Department'],
        'librarian' => ['head_librarian' => 'Main Librarian'],
        'nurse' => ['head_nurse' => 'Head Nurse'],
        'hr' => ['hr_manager' => 'HR Manager'],
        'bursar' => ['head_bursar' => 'Head Bursar'],
    ];

    public function index(Request $request): View
    {
        $roleFilter = $request->query('role');

        $users = User::with('roles')
            ->where('school_id', $request->user()->school_id)
            ->when($roleFilter, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $roleFilter)))
            ->orderBy('name')
            ->get();

        $allUsers = $roleFilter ? User::with('roles')->where('school_id', $request->user()->school_id)->get() : $users;

        return view('users.index', [
            'users' => $users,
            'roleFilter' => $roleFilter,
            'roleCounts' => $allUsers->flatMap->roles->countBy('name'),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            // Parent accounts are now provisioned automatically from a student's record (see
            // StudentController) — this screen is for staff/learner accounts only. Existing
            // parent-role users are still editable via edit() below.
            'roles' => self::BASE_ROLES,
            'tagsByRole' => self::DISTINCTION_TAGS,
            'unlinkedStudents' => Student::whereNull('user_id')->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($password = Str::password(12)),
            'school_id' => $request->user()->school_id,
            'phone' => $validated['phone'] ?? null,
            'email_verified_at' => now(),
        ]);

        $user->assignRole([$validated['role'], ...($validated['tags'] ?? [])]);

        $this->syncRoleProfile($request, $user, $validated);

        return redirect()->route('users.index')->with([
            'status' => "{$user->name} created.",
            'generatedPassword' => $password,
            'generatedPasswordFor' => $user->email,
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($user->school_id === $request->user()->school_id, 403);

        $user->load('roles', 'guardian.students', 'staffProfile');
        $linkedStudent = Student::where('user_id', $user->id)->first();
        $allTagKeys = collect(self::DISTINCTION_TAGS)->flatMap(fn ($tags) => array_keys($tags));

        return view('users.edit', [
            'targetUser' => $user,
            'roles' => [...self::BASE_ROLES, 'parent'],
            'tagsByRole' => self::DISTINCTION_TAGS,
            'currentTags' => $user->roles->pluck('name')->intersect($allTagKeys)->values(),
            'students' => Student::orderBy('first_name')->get(),
            'unlinkedStudents' => Student::whereNull('user_id')
                ->orWhere('user_id', $linkedStudent?->id)
                ->orderBy('first_name')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'linkedStudent' => $linkedStudent,
            'linkedChildIds' => $user->guardian?->students->pluck('id')->all() ?? [],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->school_id === $request->user()->school_id, 403);

        $validated = $this->validateUser($request, $user);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        $user->syncRoles([$validated['role'], ...($validated['tags'] ?? [])]);

        $this->syncRoleProfile($request, $user, $validated);

        return redirect()->route('users.index')->with('status', "{$user->name} updated.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->school_id === $request->user()->school_id, 403);
        abort_if($user->id === $request->user()->id, 422, "You can't deactivate your own account.");

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', $user->is_active ? "{$user->name} reactivated." : "{$user->name} deactivated.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $editing = null): array
    {
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($editing?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            // 'parent' isn't offered on the form but stays valid so an existing parent account
            // can still be saved through this same edit path.
            'role' => ['required', Rule::in([...self::BASE_ROLES, 'parent'])],
            'tags' => ['nullable', 'array'],
            'tags.*' => [Rule::in(collect(self::DISTINCTION_TAGS)->flatMap(fn ($tags) => array_keys($tags)))],

            // parent — needs at least one child, either picked from child_ids or filled in via
            // the new_child_* fields; enforced below, not by a single field's required_if, since
            // either source on its own is a valid submission.
            'relationship_to_student' => ['required_if:role,parent', 'nullable', 'string', 'max:50'],
            'child_ids' => ['nullable', 'array'],
            'child_ids.*' => [Rule::exists('students', 'id')->where('school_id', $request->user()->school_id)],
            'new_child_first_name' => ['nullable', 'string', 'max:255'],
            'new_child_last_name' => ['nullable', 'string', 'max:255'],
            'new_child_dob' => ['nullable', 'date'],
            'new_child_gender' => ['nullable', 'in:male,female'],
            'new_child_curriculum_level' => ['required_with:new_child_first_name', 'nullable', 'in:nursery,primary,lower_secondary,upper_secondary'],
            'new_child_school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('school_id', $request->user()->school_id)],
            // Admin-uploaded at creation time; a parent can add/replace it later via
            // StudentPhotoController for their own child specifically.
            'new_child_photo' => ['nullable', 'image', 'max:4096'],

            // learner
            'learner_student_id' => [
                'required_if:role,learner', 'nullable',
                Rule::exists('students', 'id')->where('school_id', $request->user()->school_id),
            ],

            // staff — photo is admin-only, there's no parent-equivalent upload path for it
            'trn' => ['nullable', 'string', 'max:20'],
            'role_title' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'hire_date' => ['nullable', 'date'],
            'monthly_gross_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $hasChild = ! empty($request->input('child_ids')) || $request->filled('new_child_first_name');

            if ($request->input('role') === 'parent' && ! $hasChild) {
                $validator->errors()->add('child_ids', 'Select at least one existing child or add a new one.');
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncRoleProfile(Request $request, User $user, array $validated): void
    {
        if ($validated['role'] === 'parent') {
            $guardian = Guardian::updateOrCreate(
                ['user_id' => $user->id],
                ['relationship_to_student' => $validated['relationship_to_student'] ?? 'guardian']
            );

            $childIds = $validated['child_ids'] ?? [];

            if (! empty($validated['new_child_first_name'])) {
                $child = Student::create([
                    'school_id' => $request->user()->school_id,
                    'school_class_id' => $validated['new_child_school_class_id'] ?? null,
                    'admission_no' => 'ADM-'.Str::upper(Str::random(6)),
                    'first_name' => $validated['new_child_first_name'],
                    'last_name' => $validated['new_child_last_name'] ?? '',
                    'dob' => $validated['new_child_dob'] ?? null,
                    'gender' => $validated['new_child_gender'] ?? 'male',
                    // Required, not defaulted — picking the right level (nursery vs primary vs
                    // secondary) keeps a student out of modules that don't apply to them (e.g. a
                    // primary learner showing up in nursery daily-log/milestone screens).
                    'curriculum_level' => $validated['new_child_curriculum_level'],
                    'photo_path' => isset($validated['new_child_photo'])
                        ? $validated['new_child_photo']->store('photos/students', 'public')
                        : null,
                ]);

                $childIds[] = $child->id;
            }

            $guardian->students()->sync($childIds);
        }

        if ($validated['role'] === 'learner') {
            // A learner user always maps to exactly one Student record — clear any previous
            // link (role change away from a different student) before setting the new one.
            Student::where('user_id', $user->id)->update(['user_id' => null]);
            Student::where('id', $validated['learner_student_id'])->update(['user_id' => $user->id]);
        }

        if (in_array($validated['role'], self::STAFF_ROLES, true)) {
            $staffProfile = $user->staffProfile;

            if (isset($validated['photo'])) {
                if ($staffProfile?->photo_path) {
                    Storage::disk('public')->delete($staffProfile->photo_path);
                }

                $photoPath = $validated['photo']->store('photos/staff', 'public');
            }

            StaffProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'trn' => $validated['trn'] ?? null,
                    'role_title' => $validated['role_title'] ?? ucfirst($validated['role']),
                    'hire_date' => $validated['hire_date'] ?? now(),
                    'monthly_gross_salary' => $validated['monthly_gross_salary'] ?? null,
                    // Keep the existing photo when no new one is uploaded on an edit.
                    ...(isset($photoPath) ? ['photo_path' => $photoPath] : []),
                ]
            );
        }
    }
}
