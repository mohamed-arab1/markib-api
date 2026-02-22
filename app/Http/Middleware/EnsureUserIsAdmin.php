<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (! $user || ! in_array($user->role, ['admin', 'support'])) {
            return response()->json(['message' => 'غير مصرح - تحتاج صلاحية المدير أو الدعم الفني'], 403);
        }

        return $next($request);
    }
}
