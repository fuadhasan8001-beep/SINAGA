<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AccountSessionLockManager;
use App\Support\PanelRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        $storedLogoPath = SiteSetting::query()->whereKey('school_logo_path')->value('value');
        $schoolLogoPath = json_decode((string) $storedLogoPath, true);

        return view('auth.login', [
            'schoolLogoPath' => is_string($schoolLogoPath) ? $schoolLogoPath : $storedLogoPath,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['nullable', 'string', 'max:254', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:254', 'required_without:username'],
            'password' => ['required', 'string', 'max:200'],
        ]);
        $identity = Str::lower(trim($credentials['username'] ?? $credentials['email'] ?? ''));
        // Always read the account from the database at login time. Do not use
        // cached account state or an active-only lookup that hides why login failed.
        $user = User::query()
            ->where(fn ($query) => $query->whereRaw('LOWER(username) = ?', [$identity])->orWhereRaw('LOWER(email) = ?', [$identity]))
            ->first();

        if (! $user && ! str_contains($identity, '@')) {
            $legacyMatches = User::query()
                ->whereNull('username')
                ->get()
                ->filter(fn (User $candidate): bool => Str::lower(Str::before($candidate->email, '@')) === $identity);

            if ($legacyMatches->count() === 1) {
                $user = $legacyMatches->first();
            }
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => 'Username/email atau kata sandi salah.',
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Administrator.',
            ]);
        }

        // Recheck immediately before creating a session so a status change made
        // while the password is being verified cannot authorize a stale account.
        $user = User::query()->find($user->id);
        if (! $user || ! $user->active) {
            throw ValidationException::withMessages([
                'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Administrator.',
            ]);
        }

        $request->session()->regenerate();
        // Keep ownership attached to the authenticated session even if Laravel rotates its ID.
        $sessionId = (string) Str::uuid();
        $request->session()->put('account_session_lock', $sessionId);
        $locks = app(AccountSessionLockManager::class);

        if (! $locks->acquire($user->id, $sessionId)) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'username' => 'Akun ini sedang digunakan oleh pengguna lain. Silakan gunakan akun lain atau coba lagi nanti.',
            ]);
        }

        try {
            // Do not issue a remember-me cookie that could restore auth without a lock.
            Auth::login($user, false);
            ActivityLog::create(['user_id' => $user->id, 'action' => 'login', 'detail' => 'Masuk ke panel']);
        } catch (Throwable $exception) {
            $locks->release($user->id, $sessionId);
            Auth::logout();
            throw $exception;
        }

        return redirect()->route(PanelRoute::name($user->role === 'teacher' ? 'guru.dashboard' : 'admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            ActivityLog::create(['user_id' => $user->id, 'action' => 'logout', 'detail' => 'Keluar dari panel']);
            $lockId = $request->session()->pull('account_session_lock');
            if (is_string($lockId)) {
                app(AccountSessionLockManager::class)->release($user->id, $lockId);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kamu berhasil logout.');
    }
}
