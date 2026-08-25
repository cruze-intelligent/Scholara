<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentTag;
use App\Models\User;
use App\Services\Academics\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A staff-facing student directory + profile — per feedback that staff need to actually look a
 * student up by name/admission number (previously every module had its own scattered student
 * picker, with no single "here's everything about this student" page), and that different roles
 * should be able to flag a student from their own perspective (a librarian marking overdue-book
 * defaulters, etc) rather than only ever editing a health record or an invoice in isolation.
 */
class StudentController extends Controller
{
    // HR manages staff, not students — deliberately excluded (see docs/HARDENING_TODO.md).
    private const STAFF_ROLES = ['admin', 'teacher', 'nurse', 'bursar', 'librarian'];

    /**
     * Which role can attach which tag — kept as an explicit map rather than free text, so a
     * defaulter flag always means the same thing everywhere it's shown.
     */
    private const TAGS_BY_ROLE = [
        'librarian' => ['library_defaulter' => 'Library defaulter (overdue book/fine)'],
        'head_librarian' => ['library_manager_flag' => 'Library management flag'],
        'bursar' => ['fee_defaulter' => 'Fee defaulter'],
        'head_bursar' => ['fee_escalation' => 'Fee escalation'],
        'nurse' => ['medical_alert' => 'Medical alert'],
        'head_nurse' => ['health_escalation' => 'Health escalation'],
        'teacher' => ['academic_concern' => 'Academic concern'],
        'class_teacher' => ['behaviour_note' => 'Behaviour note (homeroom)'],
    ];

    /**
     * Department leads (a "head_*" or equivalent distinction tag) can remove any tag in their
     * own scope, not just ones they personally added — the same authority an admin has, but
     * limited to their department instead of every tag on the platform.
     */
    private const DEPARTMENT_LEAD_ROLES = ['head_librarian', 'head_bursar', 'head_nurse', 'class_teacher', 'hr_manager', 'head_of_department'];

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasAnyRole(self::STAFF_ROLES), 403);

        $search = $request->query('search');

        $students = Student::with('schoolClass')
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('admission_no', 'like', "%{$search}%")
            ))
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('students.index', compact('students', 'search'));
    }

    public function create(): View
    {
        abort_unless(request()->user()->hasRole('admin'), 403);

        return view('students.create', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'streams' => Stream::orderBy('name')->get(),
        ]);
    }

    /**
     * Admin-only enrollment — creating a student always provisions its guardian's login too
     * (a child never exists without a parent in practice), reusing an existing parent account
     * by phone/email when one already matches, so siblings share one login instead of getting a
     * duplicate account each. This replaces the old flow of an admin manually adding a "parent"
     * user and typing the child in as a side effect — see UserController, which no longer offers
     * parent as a role to create directly.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $this->validateStudent($request);

        $student = Student::create([
            'school_id' => $request->user()->school_id,
            'school_class_id' => $validated['school_class_id'] ?? null,
            'stream_id' => $validated['stream_id'] ?? null,
            'admission_no' => ! empty($validated['admission_no']) ? $validated['admission_no'] : 'ADM-'.Str::upper(Str::random(6)),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? '',
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? 'male',
            'curriculum_level' => $validated['curriculum_level'],
            'photo_path' => isset($validated['photo']) ? $validated['photo']->store('photos/students', 'public') : null,
        ]);

        [$guardian, $generatedPassword] = $this->findOrCreateGuardian($request, $validated);
        $guardian->students()->attach($student->id);

        return redirect()->route('students.show', $student)->with(array_filter([
            'status' => "{$student->full_name} enrolled.".($generatedPassword ? ' A guardian login was created.' : ' Linked to an existing guardian account.'),
            'generatedPassword' => $generatedPassword,
            'generatedPasswordFor' => $generatedPassword ? $guardian->user->email ?? $guardian->user->phone : null,
        ]));
    }

    public function edit(Request $request, Student $student): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $student->load('guardians.user');

        return view('students.edit', [
            'student' => $student,
            'classes' => SchoolClass::orderBy('name')->get(),
            'streams' => Stream::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
            'curriculum_level' => ['required', 'in:nursery,primary,lower_secondary,upper_secondary'],
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('school_id', $request->user()->school_id)],
            'stream_id' => ['nullable', Rule::exists('streams', 'id')->where('school_id', $request->user()->school_id)],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $student->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? '',
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? $student->gender,
            'curriculum_level' => $validated['curriculum_level'],
            'school_class_id' => $validated['school_class_id'] ?? null,
            'stream_id' => $validated['stream_id'] ?? null,
            ...(isset($validated['photo']) ? ['photo_path' => $validated['photo']->store('photos/students', 'public')] : []),
        ]);

        return redirect()->route('students.show', $student)->with('status', 'Student updated.');
    }

    /**
     * Adds a second (or first, retroactive) guardian to an already-existing student — the same
     * find-or-reuse-by-phone/email logic as enrollment, so a CSV-imported or otherwise
     * guardian-less student can be linked from their own profile instead of only via a parent's
     * account.
     */
    public function storeGuardian(Request $request, Student $student): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:30', 'required_without:guardian_email'],
            'guardian_email' => ['nullable', 'email', 'max:255', 'required_without:guardian_phone'],
            'relationship_to_student' => ['nullable', 'string', 'max:50'],
        ]);

        [$guardian] = $this->findOrCreateGuardian($request, $validated);
        $guardian->students()->syncWithoutDetaching([$student->id]);

        return redirect()->route('students.edit', $student)->with('status', 'Guardian linked.');
    }

    /**
     * @return array{0: array<string, mixed>}
     */
    private function validateStudent(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
            'curriculum_level' => ['required', 'in:nursery,primary,lower_secondary,upper_secondary'],
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('school_id', $request->user()->school_id)],
            'stream_id' => ['nullable', Rule::exists('streams', 'id')->where('school_id', $request->user()->school_id)],
            'admission_no' => ['nullable', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:30', 'required_without:guardian_email'],
            'guardian_email' => ['nullable', 'email', 'max:255', 'required_without:guardian_phone'],
            'relationship_to_student' => ['nullable', 'string', 'max:50'],
        ]);
    }

    /**
     * Finds an existing guardian by phone or email at this school and reuses it (siblings share
     * one login); otherwise provisions a brand-new parent account with a generated password,
     * same one-time-reveal pattern as UserController's staff/learner creation.
     *
     * @param  array<string, mixed>  $validated
     * @return array{0: Guardian, 1: ?string} [guardian, generatedPassword-or-null]
     */
    private function findOrCreateGuardian(Request $request, array $validated): array
    {
        $schoolId = $request->user()->school_id;
        $phone = $validated['guardian_phone'] ?? null;
        $email = $validated['guardian_email'] ?? null;

        $existingUser = User::where('school_id', $schoolId)
            ->where(function ($q) use ($phone, $email) {
                if ($phone) {
                    $q->orWhere('phone', $phone);
                }
                if ($email) {
                    $q->orWhere('email', $email);
                }
            })
            ->first();

        if ($existingUser) {
            $guardian = Guardian::firstOrCreate(
                ['user_id' => $existingUser->id],
                ['relationship_to_student' => $validated['relationship_to_student'] ?? 'guardian']
            );

            return [$guardian, null];
        }

        $generatedPassword = Str::password(12);

        $user = User::create([
            'school_id' => $schoolId,
            'name' => $validated['guardian_name'],
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($generatedPassword),
            // Admin-vouched at creation (same as UserController's staff/learner accounts) rather
            // than sent through the real email-verification flow — that flow is reserved for
            // self-registration, where nobody has vouched for the registrant yet. A phone-only
            // guardian has no email to verify regardless, so this also keeps them from being
            // locked out by the `verified` middleware now that MustVerifyEmail is active.
            'email_verified_at' => now(),
        ]);
        $user->assignRole('parent');

        $guardian = Guardian::create([
            'user_id' => $user->id,
            'relationship_to_student' => $validated['relationship_to_student'] ?? 'guardian',
        ]);

        return [$guardian, $generatedPassword];
    }

    public function show(Request $request, Student $student, GradingService $grading): View
    {
        $this->authorizeView($request, $student);

        $student->load(['schoolClass', 'tags.taggedBy']);

        $availableTags = collect();
        foreach (self::TAGS_BY_ROLE as $role => $roleTags) {
            if ($request->user()->hasRole($role) || $request->user()->hasRole('admin')) {
                $availableTags = $availableTags->merge($roleTags);
            }
        }

        $performance = $this->canSeeAnalytics($request, $student) ? $this->performanceOverTime($student, $grading) : null;

        return view('students.show', [
            'student' => $student,
            'availableTags' => $availableTags,
            'performance' => $performance,
        ]);
    }

    public function storeTag(Request $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        $roleTags = collect();
        foreach (self::TAGS_BY_ROLE as $role => $tags) {
            if ($user->hasRole($role) || $user->hasRole('admin')) {
                $roleTags = $roleTags->merge($tags);
            }
        }

        $validated = $request->validate([
            'tag' => ['required', 'in:'.$roleTags->keys()->implode(',')],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        StudentTag::create([
            'student_id' => $student->id,
            'tag' => $validated['tag'],
            'note' => $validated['note'] ?? null,
            'tagged_by' => $user->id,
        ]);

        return back()->with('status', 'Tag added.');
    }

    public function destroyTag(Request $request, StudentTag $tag): RedirectResponse
    {
        $user = $request->user();
        $isDepartmentLead = $user->hasAnyRole(self::DEPARTMENT_LEAD_ROLES);

        abort_unless($tag->tagged_by === $user->id || $user->hasRole('admin') || $isDepartmentLead, 403);

        $tag->delete();

        return back()->with('status', 'Tag removed.');
    }

    private function authorizeView(Request $request, Student $student): void
    {
        $user = $request->user();

        if ($user->hasAnyRole(self::STAFF_ROLES)) {
            return;
        }

        if ($user->hasRole('parent')) {
            abort_unless($user->guardian?->students->contains($student->id), 403);

            return;
        }

        if ($user->hasRole('learner')) {
            abort_unless($student->user_id === $user->id, 403);

            return;
        }

        abort(403);
    }

    private function canSeeAnalytics(Request $request, Student $student): bool
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            // A class teacher sees their whole homeroom's performance, not just the subjects
            // they personally teach there — a subject teacher without that distinction only
            // sees classes/subjects they're actually assigned to.
            if ($user->hasRole('class_teacher') && $student->schoolClass?->teacher_id === $user->id) {
                return true;
            }

            return $user->teacherSubjectAssignments()->where('school_class_id', $student->school_class_id)->exists();
        }

        if ($user->hasRole('parent')) {
            return $user->guardian?->students->contains($student->id) ?? false;
        }

        if ($user->hasRole('learner')) {
            return $student->user_id === $user->id;
        }

        return false;
    }

    /**
     * A student's composite score per subject, per term, across every term on record — "track
     * one child's performance over the years" rather than only the current term's raw scores.
     */
    private function performanceOverTime(Student $student, GradingService $grading): array
    {
        $scores = $student->assessmentScores()->with('assessment.subject')->get();
        $terms = $scores->pluck('assessment.term')->unique()->sort()->values();

        return $terms->map(function ($term) use ($scores, $grading) {
            $termScores = $scores->filter(fn ($score) => $score->assessment->term === $term);

            $bySubject = $termScores->groupBy(fn ($score) => $score->assessment->subject->name)
                ->map(fn ($subjectScores) => $grading->compositeScore($subjectScores));

            return [
                'term' => $term,
                'average' => $bySubject->filter()->isNotEmpty() ? round($bySubject->filter()->avg(), 2) : null,
                'subjects' => $bySubject,
            ];
        })->values()->all();
    }
}
