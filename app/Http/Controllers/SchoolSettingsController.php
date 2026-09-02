<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin-only, one school (the acting admin's own) per request — see docs/HARDENING_TODO.md.
 * Curriculum levels the school offers (gates level-specific modules out of the nav), and the
 * school logo, which every generated PDF (report card, payslip, receipt) embeds in its header —
 * see School::logoDataUri() and resources/views/pdf/_header.blade.php.
 */
class SchoolSettingsController extends Controller
{
    private const LEVELS = ['nursery', 'primary', 'lower_secondary', 'upper_secondary'];

    public function edit(Request $request): View
    {
        $school = $request->user()->school;

        return view('school-settings.edit', [
            'school' => $school,
            'availableLevels' => self::LEVELS,
            'subscriptions' => $school->subscriptions()->latest('period_end')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'levels' => ['required', 'array', 'min:1'],
            'levels.*' => ['in:'.implode(',', self::LEVELS)],
            'logo' => ['nullable', 'image', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $school = $request->user()->school;

        $logoPath = $school->logo_path;
        if ($request->boolean('remove_logo') && $logoPath) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }
        if ($request->hasFile('logo')) {
            if ($school->logo_path) {
                Storage::disk('public')->delete($school->logo_path);
            }
            $logoPath = $validated['logo']->store('logos', 'public');
        }

        $school->update([
            'settings' => [...$school->settings ?? [], 'levels' => $validated['levels']],
            'logo_path' => $logoPath,
        ]);

        return redirect()->route('school-settings.edit')->with('status', 'School settings updated.');
    }
}
