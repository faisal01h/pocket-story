<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     *
     * Permissions can be passed as:
     * - Comma-separated for OR logic: 'permission:edit,delete'
     * - Pipe-separated for AND logic: 'permission:edit|delete'
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        if (! $request->user()) {
            abort(403, 'Unauthenticated.');
        }

        // Check for AND logic (pipe-separated)
        if (str_contains($permissions, '|')) {
            $permissionArray = explode('|', $permissions);

            if (! $request->user()->hasAllPermissions($permissionArray)) {
                abort(403, 'You do not have the required permissions to access this resource.');
            }

            return $next($request);
        }

        // Check for OR logic (comma-separated)
        $permissionArray = explode(',', $permissions);

        if (! $request->user()->hasAnyPermission($permissionArray)) {
            abort(403, 'You do not have the required permissions to access this resource.');
        }

        return $next($request);
    }
}
