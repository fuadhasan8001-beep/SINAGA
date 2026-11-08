<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\AccountSessionLockManager;
use Tests\TestCase;

class AdminTeacherImpersonationTest extends TestCase
{
    public function test_admin_can_enter_an_idle_teacher_account_and_return_without_losing_admin_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->post(route('admin.users.impersonate', $teacher))->assertRedirect(route('guru.dashboard'));
        $this->get(route('guru.dashboard'))->assertOk()->assertSee('Anda sedang masuk sebagai '.$teacher->name);
        app(AccountSessionLockManager::class)->pruneExpired();
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $admin->id]);
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $teacher->id]);

        $this->post(route('impersonation.return'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk();
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $admin->id]);
        $this->assertDatabaseMissing('account_session_locks', ['user_id' => $teacher->id]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'mulai impersonasi guru']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'akhiri impersonasi guru']);
    }

    public function test_admin_cannot_impersonate_a_teacher_with_an_active_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        app(AccountSessionLockManager::class)->acquire($teacher->id, 'active-teacher-lock');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->from(route('admin.users.index'))->post(route('admin.users.impersonate', $teacher))
            ->assertRedirect(route('admin.users.index'))->assertSessionHasErrors('impersonation');
        $this->assertSame($admin->id, auth()->id());
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $teacher->id, 'session_id' => 'active-teacher-lock']);
    }

    public function test_teacher_cannot_start_impersonation(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $target = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $this->actingAs($teacher)->post(route('admin.users.impersonate', $target))->assertForbidden();
    }
}
