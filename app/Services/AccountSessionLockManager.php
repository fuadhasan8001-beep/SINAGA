<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AccountSessionLockManager
{
    public function acquire(int $userId, string $sessionId): bool
    {
        $now = now();
        $this->pruneExpired();

        return DB::transaction(function () use ($userId, $sessionId, $now): bool {
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

    public function revokeForUser(int $userId): void
    {
        DB::table('account_session_locks')->where('user_id', $userId)->delete();
    }

    public function pruneExpired(): int
    {
        $now = now();
        $deleted = DB::table('account_session_locks')
            ->where('last_seen_at', '<=', $this->expiredBefore($now))
            ->delete();

        if (config('session.driver') !== 'database') {
            return $deleted;
        }

        $sessionTable = (string) config('session.table', 'sessions');
        $activeSince = $this->expiredBefore($now)->getTimestamp();

        // A lock must represent a still-valid Laravel session. This prevents a
        // closed or cleared browser session from blocking its account until the
        // timeout while preserving the single-active-session guarantee.
        return $deleted + DB::table('account_session_locks')
            ->whereNotExists(function ($query) use ($sessionTable, $activeSince): void {
                $query->selectRaw('1')
                    ->from($sessionTable)
                    ->where(function ($query) use ($sessionTable): void {
                        // During Admin impersonation, Laravel's session row is
                        // owned by the Guru while the original Admin lock must
                        // remain tied to that same live browser session.
                        $query->whereColumn($sessionTable.'.id', 'account_session_locks.session_id')
                            ->orWhereColumn($sessionTable.'.user_id', 'account_session_locks.user_id');
                    })
                    ->where('last_activity', '>', $activeSince);
            })
            ->delete();
    }

    private function expiredBefore($now)
    {
        return $now->copy()->subMinutes(max(1, (int) config('session.lifetime', 120)));
    }
}
