<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Toggles a dashboard shortcut on/off for the acting user only — see App\Support\PinnableTabs
 * and PinnedItem. 'calendar_dismissed' is the one built-in key not listed in PinnableTabs (the
 * calendar widget is visible by default; this hides it), so the key isn't restricted to that
 * registry — just shaped like a route name so nothing unexpected gets stored.
 */
class PinnedItemController extends Controller
{
    public function store(Request $request, string $key): RedirectResponse
    {
        $this->validateKey($key);

        $request->user()->pinnedItems()->firstOrCreate(['key' => $key]);

        return back();
    }

    public function destroy(Request $request, string $key): RedirectResponse
    {
        $this->validateKey($key);

        $request->user()->pinnedItems()->where('key', $key)->delete();

        return back();
    }

    private function validateKey(string $key): void
    {
        abort_unless(preg_match('/^[a-z0-9_.-]{1,60}$/', $key) === 1, 404);
    }
}
