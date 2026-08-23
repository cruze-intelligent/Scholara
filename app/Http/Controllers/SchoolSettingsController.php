<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-only, one school (the acting admin's own) per request — see docs/HARDENING_TODO.md.
 * Currently just the curriculum levels the school offers, which gates level-specific modules
 * (e.g. Nursery) out of the nav for schools that don't run that level.
 */
class SchoolSettingsController extends Controller
{
    private const LEVELS = ['nursery', 'primary', 'lower_secondary', 'upper_secondary'];

    public function edit(Request $request): View
    {
        return view('school-settings.edit', [
            'school' => $request->user()->school,
            'availableLevels' => self::LEVELS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'levels' => ['required', 'array', 'min:1'],
            'levels.*' => ['in:'.implode(',', self::LEVELS)],
        ]);

        $school = $request->user()->school;
        $school->update(['settings' => [...$school->settings ?? [], 'levels' => $validated['levels']]]);

        return redirect()->route('school-settings.edit')->with('status', 'School settings updated.');
    }
}
