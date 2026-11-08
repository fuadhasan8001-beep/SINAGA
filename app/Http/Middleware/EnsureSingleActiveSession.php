<?php

namespace App\Http\Middleware;

use App\Services\AccountSessionLockManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleActiveSession
{
    public function __construct(private readonly AccountSessionLockManager $locks)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->locks->pruneExpired();
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $lockId = $request->session()->get('account_session_locks.'.$user->id)
            ?? $request->session()->get('account_session_lock');
        if (! is_string($lockId) || ! $this->locks->heartbeat($user->id, $lockId)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Sesi akun ini sudah berakhir. Silakan masuk kembali.',
            ]);
        }

        return $next($request);
    }
}
