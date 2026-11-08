<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\AccountSessionLockManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function editProfile(): View
    {
        return view('admin.profile');
    }

    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::where('role', 'teacher')->orderBy('name')->paginate(30),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:200'],
        ]);
        $username = $this->uniqueUsernameFromName($data['name']);

        try {
            DB::transaction(function () use ($data, $request, $username): void {
                $user = User::create([
                    'name' => $data['name'],
                    'username' => $username,
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role' => 'teacher',
                    'active' => true,
                ]);

                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'buat akun guru',
                    'detail' => $user->email,
                ]);
            });
        } catch (QueryException $exception) {
            report($exception);
            $sqlState = $exception->errorInfo[0] ?? null;
            $databaseMessage = strtolower($exception->getMessage());
            $duplicateEmail = $sqlState === '23000'
                && str_contains($databaseMessage, 'email')
                && (str_contains($databaseMessage, 'unique') || str_contains($databaseMessage, 'duplicate'));
            $message = $duplicateEmail
                ? 'Email sudah digunakan.'
                : 'Terjadi kesalahan database saat menyimpan akun Guru. Silakan coba lagi.';

            return back()->withInput($request->except(['_token', 'password']))->withErrors(['account' => $message]);
        }

        return back()->with('success', 'Akun Guru berhasil dibuat. Username login: '.$username);
    }

    public function update(Request $request,User $user): RedirectResponse
    {
        abort_unless($user->role === 'teacher', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email,'.$user->id],
            'active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'max:200'],
        ]);
        $wasActive = $user->active;

        DB::transaction(function () use ($data, $request, $user): void {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'active' => $data['active'],
            ]);

            if (filled($data['password'] ?? null)) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            if (! $user->active) {
                // The next authenticated request will see the missing lock and end that session.
                app(AccountSessionLockManager::class)->revokeForUser($user->id);
            }

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => $user->active ? 'perbarui akun guru' : 'nonaktifkan akun guru',
                'detail' => $user->email,
            ]);
        });

        $message = ! $user->active
            ? 'Akun Guru berhasil dinonaktifkan.'
            : ($wasActive ? 'Akun Guru diperbarui.' : 'Akun Guru berhasil diaktifkan.');

        return back()->with('success', $message);
    }

    public function destroy(Request $request, User $user, AccountSessionLockManager $locks): RedirectResponse
    {
        abort_unless($user->role === 'teacher', 404);

        $identity = $user->name.' ('.$user->email.')';

        DB::transaction(function () use ($request, $user, $locks, $identity): void {
            $locks->revokeForUser($user->id);
            $user->delete();

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'hapus akun guru',
                'detail' => $identity,
            ]);
        });

        return back()->with('success', 'Akun Guru berhasil dihapus.');
    }

    public function profile(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','min:2','max:120'],'current_password'=>['required_with:new_password','current_password'],'new_password'=>['nullable','string','min:8','max:200','confirmed']]);
        $user=$request->user();$user->name=$data['name'];if(!empty($data['new_password']))$user->password=Hash::make($data['new_password']);$user->save();
        return back()->with('success','Profil diperbarui.');
    }

    private function uniqueUsernameFromName(string $name): string
    {
        $base = trim($name);
        $username = $base;
        $suffix = 2;

        while (User::whereRaw('LOWER(username) = ?', [Str::lower($username)])->exists()) {
            $username = $base.' '.$suffix++;
        }

        return $username;
    }
}
