<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateLastLoginMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Run the request through the pipeline
        $response = $next($request);

        // Check if the user is authenticated
        if (Auth::check()) {
            // Update the user's last_login timestamp
            Auth::user()->update(['last_login' => now()]);
        }

        return $response;
    }
}
