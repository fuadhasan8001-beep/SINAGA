<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountSessionLockManager;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherAccountManagementTest extends TestCase
{
    public function test_admin_can_create_teacher_account_with_eight_character_hashed_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin);

        $this->get(route('admin.users.index'))->assertOk()
            ->assertSee('Buat Akun Guru')
            ->assertSee('Minimal 8 karakter. Kata sandi hanya ditampilkan kepada Admin saat dibuat.')
            ->assertSee('teacher-account-create')
            ->assertSee('data-password-toggle', false)
            ->assertSee('teacher-account-table-wrap');

        $this->post(route('admin.users.store'), [
            'name' => 'Guru Delapan',
            'email' => 'guru.delapan@example.test',
            'password' => 'password',
            'role' => 'admin', // Role is never accepted from the form.
        ])->assertRedirect()->assertSessionHas('success', 'Akun Guru berhasil dibuat. Username login: Guru Delapan');

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.users.index'))->assertOk()->assertSee('guru.delapan@example.test')->assertSee('Username login: Guru Delapan');
        $teacher = User::where('email', 'guru.delapan@example.test')->firstOrFail();
        $this->assertSame('teacher', $teacher->role);
        $this->assertSame('Guru Delapan', $teacher->username);
        $this->assertTrue($teacher->active);
        $this->assertNotSame('password', $teacher->password);
        $this->assertTrue(Hash::check('password', $teacher->password));
        $this->assertDatabaseCount('users', 2);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'username' => 'GURU DELAPAN',
            'password' => 'password',
        ])->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticatedAs($teacher);
        $this->get(route('guru.dashboard'))->assertOk()->assertSee('Kelola Konten');

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'email' => $teacher->email,
            'password' => 'password',
        ])->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticatedAs($teacher);
    }

    public function test_legacy_teacher_without_username_can_log_in_using_unique_email_local_part(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'active' => true,
            'username' => null,
            'email' => 'promax@example.test',
            'password' => Hash::make('teacher-pass-8'),
        ]);

        $this->post(route('login.store'), [
            'username' => 'promax',
            'password' => 'teacher-pass-8',
        ])->assertRedirect(route('guru.dashboard'));

        $this->assertAuthenticatedAs($teacher);
    }

    public function test_create_validation_rejects_short_password_duplicate_email_empty_name_and_invalid_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $existing = User::factory()->create(['role' => 'teacher', 'email' => 'used@example.test']);
        $this->actingAs($admin);

        $valid = ['name' => 'Guru Baru', 'email' => 'new@example.test', 'password' => 'password'];

        $this->post(route('admin.users.store'), [...$valid, 'password' => '1234567'])->assertSessionHasErrors('password');
        $this->post(route('admin.users.store'), [...$valid, 'email' => $existing->email])->assertSessionHasErrors('email');
        $this->post(route('admin.users.store'), [...$valid, 'name' => ''])->assertSessionHasErrors('name');
        $this->post(route('admin.users.store'), [...$valid, 'email' => 'bukan-email'])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 2);
    }

    public function test_admin_can_edit_teacher_details_and_password_only_changes_when_provided(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Nama Lama',
            'email' => 'lama@example.test',
            'password' => Hash::make('oldpass123'),
        ]);
        $otherTeacher = User::factory()->create(['role' => 'teacher', 'email' => 'taken@example.test']);
        $locks = app(AccountSessionLockManager::class);
        $this->assertTrue($locks->acquire($teacher->id, 'unchanged-active-session'));
        $this->actingAs($admin);

        $this->put(route('admin.users.update', $teacher), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
            'password' => '',
            'active' => '1',
        ])->assertRedirect()->assertSessionHas('success', 'Akun Guru diperbarui.');

        $teacher->refresh();
        $this->assertSame('Nama Baru', $teacher->name);
        $this->assertSame('baru@example.test', $teacher->email);
        $this->assertTrue(Hash::check('oldpass123', $teacher->password));
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $teacher->id, 'session_id' => 'unchanged-active-session']);

        $this->put(route('admin.users.update', $teacher), [
            'name' => 'Nama Baru',
            'email' => $otherTeacher->email,
            'password' => '',
            'active' => '1',
        ])->assertSessionHasErrors('email');
        $this->assertSame('baru@example.test', $teacher->fresh()->email);

        $this->put(route('admin.users.update', $teacher), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
            'password' => 'newpass8',
            'active' => '1',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('newpass8', $teacher->fresh()->password));
    }

    public function test_deactivating_teacher_revokes_only_its_active_lock_and_prevents_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@example.test',
            'password' => Hash::make('teacher123'),
        ]);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $locks = app(AccountSessionLockManager::class);
        $this->assertTrue($locks->acquire($teacher->id, 'teacher-active-session'));
        $this->assertTrue($locks->acquire($otherTeacher->id, 'other-teacher-session'));
        $this->actingAs($admin);

        $this->put(route('admin.users.update', $teacher), [
            'name' => $teacher->name,
            'email' => $teacher->email,
            'password' => '',
            'active' => '0',
        ])->assertRedirect()->assertSessionHas('success', 'Akun Guru berhasil dinonaktifkan.');

        $accountPage = $this->get(route('admin.users.index'))->assertOk()->getContent();
        preg_match_all('/<tr>.*?<\/tr>/s', $accountPage, $accountRows);
        $disabledAccountRow = collect($accountRows[0])->first(fn (string $row): bool => str_contains($row, 'teacher@example.test'));
        $this->assertNotNull($disabledAccountRow);
        $this->assertStringNotContainsString(' checked', $disabledAccountRow);

        $this->assertDatabaseMissing('account_session_locks', ['user_id' => $teacher->id]);
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $otherTeacher->id, 'session_id' => 'other-teacher-session']);

        auth()->logout();
        $this->post(route('login.store'), ['username' => $teacher->email, 'password' => 'teacher123'])
            ->assertSessionHasErrors(['username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Administrator.']);

        $this->actingAs($admin);
        $this->put(route('admin.users.update', $teacher), [
            'name' => $teacher->name,
            'email' => $teacher->email,
            'password' => '',
            'active' => '1',
        ])->assertRedirect()->assertSessionHas('success', 'Akun Guru berhasil diaktifkan.');

        auth()->logout();
        $this->post(route('login.store'), ['username' => $teacher->email, 'password' => 'teacher123'])
            ->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticatedAs($teacher->fresh());
    }
}
