<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSupport
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, ['admin', 'support'])) {
            return response()->json(['message' => 'غير مصرح - تحتاج صلاحية الدعم الفني'], 403);
        }

        return $next($request);
    }
}
