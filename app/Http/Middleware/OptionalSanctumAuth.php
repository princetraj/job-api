<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class OptionalSanctumAuth
{
    /**
     * Handle an incoming request.
     *
     * This middleware attempts to authenticate the user if a token is present,
     * but allows the request to continue even if authentication fails.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if Authorization header is present
        $token = $request->bearerToken();

        if ($token) {
            try {
                // Try to find the token in the database
                $accessToken = PersonalAccessToken::findToken($token);

                if ($accessToken && $accessToken->tokenable) {
                    // Set the authenticated user
                    Auth::setUser($accessToken->tokenable);
                }
            } catch (\Exception $e) {
                // If authentication fails, just continue without user
                // Don't throw an error - this is optional authentication
            }
        }

        return $next($request);
    }
}
