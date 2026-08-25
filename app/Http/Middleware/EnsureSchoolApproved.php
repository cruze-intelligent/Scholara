<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a school's users out of the real app while that school is pending review, suspended,
 * rejected, or (once its trial has run out) unpaid — see School::isAccessible(). A super_admin
 * isn't tied to a school at all and always passes through.
 */
class EnsureSchoolApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasRole('super_admin')) {
            return $next($request);
        }

        if ($user->school && ! $user->school->isAccessible() && ! $request->routeIs('school-status.*', 'logout')) {
            return redirect()->route('school-status.show');
        }

        return $next($request);
    }
}
