<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Check if user is blocked
        if ($request->user()->isBlocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been blocked. Please contact support.'
            ], 403);
        }

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if ($request->user()->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. You do not have permission to access this resource.'
        ], 403);
    }
}
