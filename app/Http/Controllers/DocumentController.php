<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generic file attachments — a student's medical dosage sheet/prescription (category
 * 'medical', nurse/admin-managed) and a staff member's document file (category 'staff',
 * hr/admin-managed). Two thin route-specific entry points sharing the same underlying model,
 * since "who can touch this" genuinely differs per attachment target.
 */
class DocumentController extends Controller
{
    public function studentIndex(Request $request, Student $student): View
    {
        $this->authorizeStudentAccess($request, $student);

        $documents = Document::where('documentable_type', Student::class)
            ->where('documentable_id', $student->id)
            ->with('uploader')
            ->latest()
            ->get();

        return view('documents.student-index', compact('student', 'documents'));
    }

    public function studentStore(Request $request, Student $student): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['nurse', 'admin']), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        $file = $validated['file'];

        Document::create([
            'documentable_type' => Student::class,
            'documentable_id' => $student->id,
            'uploaded_by' => $request->user()->id,
            'category' => 'medical',
            'title' => $validated['title'],
            'file_path' => $file->store('documents/medical', 'public'),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()->route('students.documents.index', $student)->with('status', 'Document uploaded.');
    }

    public function staffIndex(Request $request, User $user): View
    {
        $this->authorizeStaffAccess($request, $user);

        $documents = Document::where('documentable_type', User::class)
            ->where('documentable_id', $user->id)
            ->with('uploader')
            ->latest()
            ->get();

        return view('documents.staff-index', ['staffUser' => $user, 'documents' => $documents]);
    }

    public function staffStore(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['hr', 'admin']), 403);
        abort_unless($user->school_id === $request->user()->school_id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);

        $file = $validated['file'];

        Document::create([
            'documentable_type' => User::class,
            'documentable_id' => $user->id,
            'uploaded_by' => $request->user()->id,
            'category' => 'staff',
            'title' => $validated['title'],
            'file_path' => $file->store('documents/staff', 'public'),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()->route('users.documents.index', $user)->with('status', 'Document uploaded.');
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        if ($document->documentable_type === Student::class) {
            $this->authorizeStudentAccess($request, $document->documentable);
        } elseif ($document->documentable_type === User::class) {
            $this->authorizeStaffAccess($request, $document->documentable);
        } else {
            abort(403);
        }

        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        if ($document->documentable_type === Student::class) {
            abort_unless($request->user()->hasAnyRole(['nurse', 'admin']), 403);
            $redirect = redirect()->route('students.documents.index', $document->documentable_id);
        } else {
            abort_unless($request->user()->hasAnyRole(['hr', 'admin']), 403);
            $redirect = redirect()->route('users.documents.index', $document->documentable_id);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return $redirect->with('status', 'Document deleted.');
    }

    private function authorizeStudentAccess(Request $request, Student $student): void
    {
        $user = $request->user();

        if ($user->hasAnyRole(['nurse', 'admin'])) {
            return;
        }

        if ($user->hasRole('parent')) {
            $isOwnChild = $user->guardian?->students->contains($student->id) ?? false;
            abort_unless($isOwnChild, 403);

            return;
        }

        abort(403);
    }

    private function authorizeStaffAccess(Request $request, User $staffUser): void
    {
        $user = $request->user();

        if ($user->hasAnyRole(['hr', 'admin']) && $staffUser->school_id === $user->school_id) {
            return;
        }

        abort_unless($user->id === $staffUser->id, 403);
    }
}
