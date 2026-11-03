<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AccountSessionLockManager
{
    public function acquire(int $userId, string $sessionId): bool
    {
        $now = now();
        $expiredBefore = $this->expiredBefore($now);

        return DB::transaction(function () use ($userId, $sessionId, $now, $expiredBefore): bool {
            DB::table('account_session_locks')->where('last_seen_at', '<=', $expiredBefore)->delete();

            $inserted = DB::table('account_session_locks')->insertOrIgnore([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'last_seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($inserted === 1) {
                return true;
            }

            $updated = DB::table('account_session_locks')
                ->where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->update(['last_seen_at' => $now, 'updated_at' => $now]);

            return $updated === 1 || DB::table('account_session_locks')
                ->where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->exists();
        }, attempts: 5);
    }

    public function heartbeat(int $userId, string $sessionId): bool
    {
        DB::table('account_session_locks')
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->update(['last_seen_at' => now(), 'updated_at' => now()]);

        return DB::table('account_session_locks')
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->exists();
    }

    public function release(int $userId, string $sessionId): void
    {
        DB::table('account_session_locks')
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->delete();
    }

    public function pruneExpired(): int
    {
        return DB::table('account_session_locks')
            ->where('last_seen_at', '<=', $this->expiredBefore(now()))
            ->delete();
    }

    private function expiredBefore($now)
    {
        return $now->copy()->subMinutes(max(1, (int) config('session.lifetime', 120)));
    }
}
