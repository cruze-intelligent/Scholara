<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\WowMoment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WowMomentController extends Controller
{
    public function index(): View
    {
        $moments = WowMoment::with(['student', 'teacher'])->latest('created_at')->paginate(20);

        return view('wow-moments.index', compact('moments'));
    }

    public function create(): View
    {
        $students = Student::where('curriculum_level', 'nursery')->orderBy('first_name')->get();

        return view('wow-moments.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'caption' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        WowMoment::create([
            'student_id' => $validated['student_id'],
            'teacher_id' => $request->user()->id,
            'caption' => $validated['caption'],
            'media_path' => $request->hasFile('photo') ? $request->file('photo')->store('wow-moments', 'public') : null,
        ]);

        return redirect()->route('wow-moments.index')->with('status', 'WOW moment shared.');
    }
}
