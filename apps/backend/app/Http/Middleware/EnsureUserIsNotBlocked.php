<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $u = $request->user();
        if ($u?->is_blocked) {
            return response()->json(['message'=>'User is blocked'], Response::HTTP_FORBIDDEN);
        }
        return $next($request);
    }
}
