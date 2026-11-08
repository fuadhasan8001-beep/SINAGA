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
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function editProfile(): View
    {
        $user = request()->user();

        return view('admin.profile', [
            'profileUpdateRoute' => $user->role === 'teacher' ? route('guru.profile.update') : route('admin.profile.update'),
            'dashboardRoute' => $user->role === 'teacher' ? route('guru.dashboard') : route('admin.dashboard'),
        ]);
    }

    public function index(): View
    {
        $locks = app(AccountSessionLockManager::class);
        $locks->pruneExpired();
        return view('admin.users.index', [
            'users' => User::where('role', 'teacher')->orderBy('name')->paginate(30),
            'activeLocks' => DB::table('account_session_locks')->get()->keyBy('user_id'),
        ]);
    }

    public function impersonate(Request $request, User $user, AccountSessionLockManager $locks): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($user->role === 'teacher', 404);
        abort_unless($user->active, 422, 'Akun Guru tidak aktif.');

        $admin = $request->user();
        $adminLock = $request->session()->get('account_session_locks.'.$admin->id)
            ?? $request->session()->get('account_session_lock');
        abort_unless(is_string($adminLock), 401);
        $sessionLockId = (string) $request->session()->getId();
        // Tie both role locks to the same live Laravel browser session so
        // pruning cannot mistake the paused Admin lock for an orphan.
        DB::table('account_session_locks')->where('user_id', $admin->id)->where('session_id', $adminLock)
            ->update(['session_id' => $sessionLockId, 'last_seen_at' => now(), 'updated_at' => now()]);
        $adminLock = $sessionLockId;
        $request->session()->put('account_session_lock', $adminLock);
        $request->session()->put('account_session_locks.'.$admin->id, $adminLock);
        $teacherLock = $sessionLockId;
        if (! $locks->acquire($user->id, $teacherLock)) {
            return back()->withErrors(['impersonation' => 'Akun Guru sedang digunakan. Pengambilalihan ditolak.']);
        }

        $request->session()->put('admin_impersonation', [
            'admin_id' => $admin->id,
            'admin_lock' => $adminLock,
            'teacher_id' => $user->id,
            'teacher_lock' => $teacherLock,
        ]);
        $request->session()->put('account_session_locks.'.$user->id, $teacherLock);
        Auth::login($user, false);
        ActivityLog::create(['user_id' => $admin->id, 'action' => 'mulai impersonasi guru', 'detail' => $user->name.' ('.$user->email.')']);
        return redirect()->route('guru.dashboard')->with('success', 'Anda sedang masuk sebagai '.$user->name.'.');
    }

    public function stopImpersonation(Request $request, AccountSessionLockManager $locks): RedirectResponse
    {
        $impersonation = $request->session()->get('admin_impersonation');
        abort_unless(is_array($impersonation), 403);
        $admin = User::query()->whereKey($impersonation['admin_id'] ?? 0)->where('role', 'admin')->where('active', true)->firstOrFail();
        $teacher = $request->user();
        abort_unless($teacher && (int) $teacher->id === (int) ($impersonation['teacher_id'] ?? 0), 403);
        $locks->release($teacher->id, (string) $impersonation['teacher_lock']);
        $request->session()->forget(['admin_impersonation', 'account_session_locks.'.$teacher->id]);
        $request->session()->put('account_session_locks.'.$admin->id, (string) $impersonation['admin_lock']);
        Auth::login($admin, false);
        ActivityLog::create(['user_id' => $admin->id, 'action' => 'akhiri impersonasi guru', 'detail' => $teacher->name.' ('.$teacher->email.')']);
        return redirect()->route('admin.dashboard')->with('success', 'Anda kembali ke akun Admin.');
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
