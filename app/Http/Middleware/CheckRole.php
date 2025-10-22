<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            Log::warning('CheckRole: No authenticated user', [
                'url' => $request->fullUrl(),
                'required_roles' => $roles
            ]);

            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            Log::warning('CheckRole: User role mismatch', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'required_roles' => $roles,
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'message' => 'Unauthorized. Insufficient permissions.',
                'debug' => config('app.debug') ? [
                    'user_role' => $user->role,
                    'required_roles' => $roles
                ] : null
            ], 403);
        }

        return $next($request);
    }
}
