<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    public function actingAs(UserContract $user, $guard = null)
    {
        parent::actingAs($user, $guard);

        if ($user instanceof User && Schema::hasTable('account_session_locks')) {
            $session = $this->app['session']->driver();
            $session->start();
            $now = now();
            $lockId = (string) Str::uuid();
            $session->put('account_session_lock', $lockId);

            DB::table('account_session_locks')->updateOrInsert(
                ['user_id' => $user->id],
                ['session_id' => $lockId, 'last_seen_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        return $this;
    }
}
