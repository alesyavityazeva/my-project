<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Usage in routes: ->middleware('role:1') or ->middleware('role:2') or ->middleware('role:0,1,2')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = (string) auth()->user()->id_roli;

        if (!in_array($userRole, $roles)) {
            abort(403, 'Доступ запрещен.');
        }

        return $next($request);
    }
}
