<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperadminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperadmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Superadmin access required.'], 403);
            }

            abort(403, 'Unauthorized. Superadmin access required.');
        }

        return $next($request);
    }
}
