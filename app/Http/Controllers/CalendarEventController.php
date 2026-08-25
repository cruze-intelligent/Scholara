<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The academic calendar (term dates, holidays, exam periods, deadlines) — admin-authored, but
 * readable by every role so the same set of dates is what everyone sees, rather than each module
 * (attendance, invoices, exams) quietly assuming its own term boundaries.
 */
class CalendarEventController extends Controller
{
    public function index(Request $request): View
    {
        // A super_admin has no school_id, so BelongsToSchool's scope wouldn't filter this query
        // at all — every school's dates mixed together. Not their concern; keep them out.
        abort_if($request->user()->hasRole('super_admin'), 403);

        $today = today();

        $events = CalendarEvent::orderBy('start_date')->get();

        return view('calendar.index', [
            'upcoming' => $events->filter(fn ($event) => ($event->end_date ?? $event->start_date) >= $today)->values(),
            'past' => $events->filter(fn ($event) => ($event->end_date ?? $event->start_date) < $today)->sortByDesc('start_date')->values(),
        ]);
    }

    public function create(): View
    {
        return view('calendar.create', ['categories' => CalendarEvent::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEvent($request);

        CalendarEvent::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('calendar.index')->with('status', 'Calendar event added.');
    }

    public function edit(CalendarEvent $calendarEvent): View
    {
        return view('calendar.edit', ['event' => $calendarEvent, 'categories' => CalendarEvent::CATEGORIES]);
    }

    public function update(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $calendarEvent->update($this->validateEvent($request));

        return redirect()->route('calendar.index')->with('status', 'Calendar event updated.');
    }

    public function destroy(CalendarEvent $calendarEvent): RedirectResponse
    {
        $calendarEvent->delete();

        return redirect()->route('calendar.index')->with('status', 'Calendar event removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', 'in:'.implode(',', array_keys(CalendarEvent::CATEGORIES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }
}
