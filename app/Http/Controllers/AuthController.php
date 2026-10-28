<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View { return view('auth.login'); }

    public function login(Request $request): RedirectResponse
    {
        $credentials=$request->validate(['email'=>['required','email','max:254'],'password'=>['required','string','max:200']]);
        if(!Auth::attempt([...$credentials,'active'=>true],$request->boolean('remember'))){
            throw ValidationException::withMessages(['email'=>'Email atau kata sandi salah, atau akun sedang dinonaktifkan.']);
        }
        $request->session()->regenerate();
        ActivityLog::create(['user_id'=>Auth::id(),'action'=>'login','detail'=>'Masuk ke panel']);
        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        ActivityLog::create(['user_id'=>Auth::id(),'action'=>'logout','detail'=>'Keluar dari panel']);
        Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
