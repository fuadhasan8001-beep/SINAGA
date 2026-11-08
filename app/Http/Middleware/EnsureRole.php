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
        // Old bookmarks or a cached Admin-panel link should take a teacher to
        // the equivalent protected Guru route for content they may manage.
        // This only applies to safe page requests and does not grant Admin
        // access or redirect writes through the Admin endpoints.
        if ($user->role === 'teacher' && in_array('admin', $roles, true)
            && in_array($request->method(), ['GET', 'HEAD'], true)) {
            $segments = explode('/', trim($request->path(), '/'));
            if (($segments[0] ?? null) === 'admin' && ($segments[1] ?? null) === 'content'
                && ($segments[2] ?? null) === 'extracurricular'
                && ($segments[3] ?? null) === 'create' && count($segments) === 4) {
                $path = '/guru'.substr($request->getPathInfo(), strlen('/admin'));
                if ($request->getQueryString()) $path .= '?'.$request->getQueryString();
                return redirect($path);
            }
        }
        abort_unless(in_array($user->role,$roles,true),403);
        return $next($request);
    }
}
