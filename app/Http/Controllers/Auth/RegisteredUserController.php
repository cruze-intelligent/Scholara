<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * A school's self-registration — not a generic "create any account" form (that stock Breeze
 * behaviour would create a schoolless, roleless user, which nothing else in this app expects).
 * The registrant becomes the school's admin; the school starts 'pending_review' and the admin's
 * email starts unverified, since nobody has vouched for either yet — see
 * EnsureSchoolApproved and School::isAccessible().
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_address' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'alpha_dash', 'max:63', Rule::unique('schools', 'subdomain')],
            // A documented placeholder identifier (like the PAYE/NSSF rates elsewhere in this
            // app) — verify the real Ministry of Education requirement before relying on it.
            'moe_registration_number' => ['nullable', 'string', 'max:100'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $school = School::create([
            'name' => $validated['school_name'],
            'address' => $validated['school_address'],
            'subdomain' => $validated['subdomain'],
            'moe_registration_number' => $validated['moe_registration_number'] ?? null,
            'registration_number' => School::generateRegistrationNumber(),
            'status' => 'pending_review',
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'phone' => $validated['admin_phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => null,
        ]);
        $user->assignRole('admin');

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
