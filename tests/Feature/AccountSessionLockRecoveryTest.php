<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountSessionLockManager;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountSessionLockRecoveryTest extends TestCase
{
    public function test_an_orphaned_lock_does_not_block_a_new_login(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['role' => 'admin', 'active' => true]);
        $now = now();

        DB::table('account_session_locks')->insert([
            'user_id' => $user->id,
            'session_id' => 'orphaned-lock',
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $locks = app(AccountSessionLockManager::class);

        $this->assertTrue($locks->acquire($user->id, 'new-login-lock'));
        $this->assertDatabaseHas('account_session_locks', [
            'user_id' => $user->id,
            'session_id' => 'new-login-lock',
        ]);
    }

    public function test_a_lock_with_an_active_laravel_session_remains_protected(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['role' => 'admin', 'active' => true]);
        $now = now();

        DB::table('account_session_locks')->insert([
            'user_id' => $user->id,
            'session_id' => 'active-lock',
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('sessions')->insert([
            'id' => 'active-browser-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => $now->getTimestamp(),
        ]);

        $this->assertFalse(app(AccountSessionLockManager::class)->acquire($user->id, 'second-login-lock'));
        $this->assertDatabaseHas('account_session_locks', [
            'user_id' => $user->id,
            'session_id' => 'active-lock',
        ]);
    }
}
