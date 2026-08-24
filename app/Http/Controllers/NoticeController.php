<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Notice;
use App\Models\Student;
use App\Models\User;
use App\Notifications\NoticePublished;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function index(): View
    {
        $notices = Notice::with('author')->latest('created_at')->paginate(15);

        return view('notices.index', compact('notices'));
    }

    public function create(): View
    {
        return view('notices.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'audience' => ['required', 'string', 'max:50'],
        ]);

        Notice::create([
            'author_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'audience' => $validated['audience'],
            'published_at' => $request->boolean('publish') ? now() : null,
        ]);

        return redirect()->route('notices.index')->with('status', 'Notice saved.');
    }

    public function publish(Notice $notice): RedirectResponse
    {
        $notice->update(['published_at' => now()]);

        $guardianUserIds = Guardian::whereHas('students', fn ($q) => $q->where('school_id', $notice->school_id))
            ->pluck('user_id');
        $learnerUserIds = Student::where('school_id', $notice->school_id)->whereNotNull('user_id')->pluck('user_id');

        Notification::send(
            User::whereIn('id', $guardianUserIds->merge($learnerUserIds)->unique())->get(),
            new NoticePublished($notice)
        );

        return back()->with('status', 'Notice published.');
    }
}
