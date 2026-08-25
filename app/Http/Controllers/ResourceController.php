<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

/**
 * Teaching resources (lesson notes, worksheets, handouts) — a teacher uploads for a class/subject
 * they're assigned to; admin can upload for any class. Parents/learners get read-only access
 * scoped to their own child's/own class, so materials a teacher shares are actually reachable by
 * the students they're for, not just stored.
 */
class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Resource::with(['teacher', 'subject', 'schoolClass']);

        if ($user->hasRole('admin')) {
            // Resource's BelongsToSchool scope already limits this to the admin's own school.
        } elseif ($user->hasRole('teacher')) {
            $classIds = $user->teacherSubjectAssignments()->pluck('school_class_id');
            $query->where(function ($q) use ($user, $classIds) {
                $q->where('teacher_id', $user->id)->orWhereIn('school_class_id', $classIds);
            });
        } elseif ($user->hasRole('parent')) {
            $classIds = $user->guardian?->students()->pluck('school_class_id') ?? collect();
            $query->whereIn('school_class_id', $classIds);
        } elseif ($user->hasRole('learner')) {
            $classId = Student::where('user_id', $user->id)->value('school_class_id');
            $query->where('school_class_id', $classId);
        }

        return view('resources.index', ['resources' => $query->latest()->get()]);
    }

    public function create(Request $request): View
    {
        $assignments = $request->user()->teacherSubjectAssignments()->with(['subject', 'schoolClass'])->get();

        return view('resources.create', compact('assignments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assignment_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png'],
        ]);

        $assignment = $request->user()->teacherSubjectAssignments()->findOrFail($validated['assignment_id']);
        $file = $validated['file'];

        Resource::create([
            'teacher_id' => $request->user()->id,
            'subject_id' => $assignment->subject_id,
            'school_class_id' => $assignment->school_class_id,
            'title' => $validated['title'],
            'file_path' => $file->store('resources', 'public'),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()->route('resources.index')->with('status', 'Resource uploaded.');
    }

    public function download(Request $request, Resource $resource): StreamedResponse
    {
        $this->authorizeView($request, $resource);

        return Storage::disk('public')->download($resource->file_path, $resource->original_filename ?? $resource->title);
    }

    public function destroy(Request $request, Resource $resource): RedirectResponse
    {
        abort_unless($resource->teacher_id === $request->user()->id || $request->user()->hasRole('admin'), 403);

        Storage::disk('public')->delete($resource->file_path);
        $resource->delete();

        return redirect()->route('resources.index')->with('status', 'Resource deleted.');
    }

    private function authorizeView(Request $request, Resource $resource): void
    {
        $user = $request->user();

        if ($user->hasRole('admin') || $resource->teacher_id === $user->id) {
            return;
        }

        if ($user->hasRole('teacher')) {
            $allowed = $user->teacherSubjectAssignments()->where('school_class_id', $resource->school_class_id)->exists();
            abort_unless($allowed, 403);

            return;
        }

        if ($user->hasRole('parent')) {
            $allowed = $user->guardian?->students()->where('school_class_id', $resource->school_class_id)->exists() ?? false;
            abort_unless($allowed, 403);

            return;
        }

        if ($user->hasRole('learner')) {
            $allowed = Student::where('user_id', $user->id)->where('school_class_id', $resource->school_class_id)->exists();
            abort_unless($allowed, 403);

            return;
        }

        abort(403);
    }
}
