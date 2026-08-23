<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Student profile/ID photo — admin can upload for any student at their school; a parent can
 * upload only for their own guardian-linked children, never any other student.
 */
class StudentPhotoController extends Controller
{
    public function update(Request $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin') && $student->school_id === $user->school_id;
        $isOwnChild = $user->hasRole('parent') && $user->guardian?->students->contains($student->id);

        abort_unless($isAdmin || $isOwnChild, 403);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:4096'],
        ]);

        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }

        $student->update([
            'photo_path' => $validated['photo']->store('photos/students', 'public'),
        ]);

        return back()->with('status', 'Photo updated.');
    }
}
