<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin-managed identification streams (e.g. "Blue", "Green") a student or teacher can be
 * attached to — see Stream model for why this is a label, not a second class hierarchy.
 */
class StreamController extends Controller
{
    public function index(): View
    {
        return view('streams.index', [
            'streams' => Stream::withCount('students', 'staffProfiles')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('streams', 'name')->where('school_id', $request->user()->school_id),
            ],
        ]);

        Stream::create($validated);

        return redirect()->route('streams.index')->with('status', 'Stream added.');
    }

    public function destroy(Stream $stream): RedirectResponse
    {
        $stream->delete();

        return redirect()->route('streams.index')->with('status', 'Stream removed.');
    }
}
