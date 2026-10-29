<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user=$request->user();
        if (!$user || !$user->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login');
        }
        abort_unless(in_array($user->role,$roles,true),403);
        $response=$next($request);
        $response->headers->set('Cache-Control','no-store, private, max-age=0, must-revalidate');
        $response->headers->set('Pragma','no-cache');
        return $response;
    }
}
