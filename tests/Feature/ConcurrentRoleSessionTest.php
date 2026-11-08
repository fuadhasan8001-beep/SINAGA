<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ConcurrentRoleSessionTest extends TestCase
{
    public function test_admin_and_teacher_accounts_keep_independent_session_locks_and_role_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $admin->id]);

        $this->actingAs($teacher)->get(route('guru.dashboard'))->assertOk();
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $admin->id]);
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $teacher->id]);
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('guru.content.create', 'extracurricular'))->assertOk();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $admin->id]);
        $this->assertDatabaseMissing('account_session_locks', ['user_id' => $teacher->id]);
    }
}
