<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Allow the request when the user has one of the given roles.
     *
     * Administrators are allowed through every role group so they can review
     * the operations of the institution.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $slug = $user->role?->slug;

        if ($slug !== null && in_array($slug, $roles, true)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this area.');
    }
}
