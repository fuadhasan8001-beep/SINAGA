<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountSessionLockManager;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class DefaultAccountsAndSingleSessionTest extends TestCase
{
    public function test_seeder_creates_seven_hashed_role_correct_accounts_and_preserves_changed_passwords(): void
    {
        $this->seed(DefaultAccountsSeeder::class);

        $this->assertSame(7, User::whereIn('username', ['guru1','guru2','guru3','guru4','guru5','admin1','admin2'])->count());

        foreach (['guru1','guru2','guru3','guru4','guru5'] as $username) {
            $user = User::where('username', $username)->firstOrFail();
            $this->assertSame('teacher', $user->role);
            $this->assertTrue(Hash::check('sinagajaya', $user->password));
        }

        foreach (['admin1','admin2'] as $username) {
            $user = User::where('username', $username)->firstOrFail();
            $this->assertSame('admin', $user->role);
            $this->assertTrue(Hash::check('sinagajaya', $user->password));
        }

        $guru = User::where('username', 'guru1')->firstOrFail();
        $guru->forceFill(['password' => Hash::make('Password-Changed-456')])->save();
        $this->seed(DefaultAccountsSeeder::class);

        $this->assertTrue(Hash::check('Password-Changed-456', $guru->fresh()->password));
        $this->assertSame(7, User::whereIn('username', ['guru1','guru2','guru3','guru4','guru5','admin1','admin2'])->count());
    }

    public function test_all_default_accounts_can_log_in_and_logout_releases_each_independent_lock(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->seed(DefaultAccountsSeeder::class);

        foreach (['guru1','guru2','guru3','guru4','guru5','admin1','admin2'] as $username) {
            $user = User::where('username', $username)->firstOrFail();

            $this->post(route('login.store'), ['username' => $username, 'password' => 'sinagajaya'])
                ->assertRedirect(route('admin.dashboard'));
            $this->assertAuthenticatedAs($user);
            $this->get(route('admin.dashboard'))->assertOk();
            $this->post(route('logout'))->assertRedirect(route('login'));
            $this->assertDatabaseMissing('account_session_locks', ['user_id' => $user->id]);
        }
    }

    public function test_active_lock_blocks_same_account_but_other_accounts_and_logout_are_independent(): void
    {
        $this->seed(DefaultAccountsSeeder::class);
        $guruOne = User::where('username', 'guru1')->firstOrFail();
        $guruTwo = User::where('username', 'guru2')->firstOrFail();

        $this->post(route('login.store'), ['username' => 'guru1', 'password' => 'sinagajaya'])
            ->assertRedirect(route('admin.dashboard'));
        $firstSessionId = (string) DB::table('account_session_locks')->where('user_id', $guruOne->id)->value('session_id');

        // Simulate another browser with no authenticated guard but the first browser's lock still present.
        Auth::logout();
        $this->post(route('login.store'), ['username' => 'guru1', 'password' => 'sinagajaya'])
            ->assertSessionHasErrors([
                'username' => 'Akun ini sedang digunakan oleh pengguna lain. Silakan gunakan akun lain atau coba lagi nanti.',
            ]);
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $guruOne->id, 'session_id' => $firstSessionId]);

        $this->post(route('login.store'), ['username' => 'guru2', 'password' => 'sinagajaya'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $guruOne->id, 'session_id' => $firstSessionId]);
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $guruTwo->id]);

        $this->post(route('logout'))->assertRedirect(route('login'));
        app(AccountSessionLockManager::class)->release($guruOne->id, $firstSessionId);
        $this->post(route('login.store'), ['username' => 'guru1', 'password' => 'sinagajaya'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $guruOne->id]);
    }

    public function test_expired_lock_is_pruned_without_releasing_a_different_active_account(): void
    {
        $this->seed(DefaultAccountsSeeder::class);
        $expiredUser = User::where('username', 'guru3')->firstOrFail();
        $activeUser = User::where('username', 'admin1')->firstOrFail();
        $locks = app(AccountSessionLockManager::class);

        $this->assertTrue($locks->acquire($expiredUser->id, 'expired-session'));
        $this->assertTrue($locks->acquire($activeUser->id, 'active-admin-session'));
        DB::table('account_session_locks')->where('user_id', $expiredUser->id)->update([
            'last_seen_at' => now()->subMinutes(config('session.lifetime') + 1),
        ]);

        $this->post(route('login.store'), ['username' => 'guru3', 'password' => 'sinagajaya'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('account_session_locks', ['user_id' => $expiredUser->id, 'session_id' => 'expired-session']);
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $activeUser->id, 'session_id' => 'active-admin-session']);
    }
}
