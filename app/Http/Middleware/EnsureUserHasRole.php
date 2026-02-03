<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Roles can be passed as:
     * - Comma-separated for OR logic: 'role:admin,moderator'
     * - Pipe-separated for AND logic: 'role:admin|moderator'
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (! $request->user()) {
            abort(403, 'Unauthenticated.');
        }

        // Check for AND logic (pipe-separated)
        if (str_contains($roles, '|')) {
            $roleArray = explode('|', $roles);

            if (! $request->user()->hasAllRoles($roleArray)) {
                abort(403, 'You do not have the required role to access this resource.');
            }

            return $next($request);
        }

        // Check for OR logic (comma-separated)
        $roleArray = explode(',', $roles);

        if (! $request->user()->hasAnyRole($roleArray)) {
            abort(403, 'You do not have the required role to access this resource.');
        }

        return $next($request);
    }
}
