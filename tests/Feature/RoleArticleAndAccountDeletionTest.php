<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ContentEntry;
use App\Models\User;
use App\Services\AccountSessionLockManager;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleArticleAndAccountDeletionTest extends TestCase
{
    public function test_admin_teacher_and_viewer_routing_stays_in_its_own_context(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);

        $adminDashboard = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $adminDashboard->assertSee(route('admin.articles.index'), false)
            ->assertSee(route('admin.content.index', 'gallery'), false)
            ->assertSee(route('admin.content.index', 'announcement'), false);
        $this->get(route('admin.articles.index'))->assertOk();
        $this->get(route('admin.content.index', 'gallery'))->assertOk();
        $this->get(route('admin.content.index', 'announcement'))->assertOk();
        $this->get(route('guru.dashboard'))->assertForbidden();

        auth()->logout();
        $guruDashboard = $this->actingAs($teacher)->get(route('guru.dashboard'))->assertOk()->assertViewIs('public.home');
        $guruDashboard->assertSee(route('guru.articles.index'), false)
            ->assertSee(route('guru.content.index', 'gallery'), false)
            ->assertSee(route('guru.content.index', 'announcement'), false);
        $this->get(route('guru.articles.index'))->assertOk();
        $this->get(route('guru.content.index', 'gallery'))->assertOk();
        $this->get(route('guru.content.index', 'announcement'))->assertOk();
        $this->get(route('admin.articles.index'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();

        $locks = app(AccountSessionLockManager::class);
        $locks->revokeForUser($admin->id);
        $locks->revokeForUser($teacher->id);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('guru.dashboard'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['email' => $teacher->email, 'password' => 'password'])
            ->assertRedirect(route('guru.dashboard'));

        auth()->logout();
        $this->get(route('information.articles'))->assertOk()->assertSee(route('information.articles'), false);
        $this->get(route('gallery'))->assertOk();
        $this->get(route('announcements'))->assertOk();
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('guru.dashboard'))->assertRedirect(route('login'));
    }

    public function test_teacher_sidebar_and_endpoints_only_expose_allowed_content_tools(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $this->actingAs($teacher);

        $dashboard = $this->get(route('guru.dashboard'))->assertOk()
            ->assertSee(route('guru.articles.index'), false)
            ->assertSee(route('guru.content.index', 'gallery'), false)
            ->assertSee(route('guru.content.index', 'announcement'), false)
            ->assertDontSee(route('admin.settings.edit'), false)
            ->assertDontSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.messages.index'), false)
            ->assertDontSee(route('admin.footer.edit'), false);
        preg_match('/<aside[^>]*id="admin-sidebar"[^>]*>(.*?)<\/aside>/s', $dashboard->getContent(), $sidebar);
        $this->assertNotEmpty($sidebar);
        $this->assertStringNotContainsString('Mode pengunjung', $sidebar[1]);

        $this->get(route('guru.articles.index'))->assertOk();
        $this->get(route('guru.content.index', 'gallery'))->assertOk();
        $this->get(route('guru.content.index', 'announcement'))->assertOk();
        $this->get(route('admin.articles.index'))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertForbidden();

        foreach ([
            route('admin.settings.edit', ['section' => 'profile']),
            route('admin.settings.edit', ['section' => 'hero']),
            route('admin.settings.edit', ['section' => 'contact']),
            route('admin.footer.edit'),
            route('admin.messages.index'),
            route('admin.users.index'),
            route('admin.profile.edit'),
            route('admin.categories.index'),
            route('admin.spmb.dashboard'),
            route('admin.content.index', 'extracurricular'),
        ] as $restrictedUrl) {
            $this->get($restrictedUrl)->assertForbidden();
        }
    }

    public function test_teacher_can_create_own_article_and_shared_gallery_and_announcement(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($teacher);

        $this->post(route('guru.articles.store'), [
            'title' => 'Kegiatan Literasi Sekolah',
            'slug' => 'literasi-sekolah',
            'excerpt' => 'Membaca bersama.',
            'body' => '<p>Kegiatan literasi berlangsung hari ini.</p>',
            'published_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'intent' => 'review',
            'author_id' => $admin->id,
        ])->assertRedirect();

        $article = Article::where('slug', 'literasi-sekolah')->firstOrFail();
        $this->assertSame($teacher->id, $article->author_id);
        $this->assertSame($teacher->name, $article->author_name);
        $this->assertSame('published', $article->status);
        $this->assertNotNull($article->published_at);

        $this->post(route('guru.content.store', 'announcement'), [
            'title' => 'Pengumuman Guru',
            'description' => 'Informasi kegiatan sekolah.',
            'published' => '1',
            'starts_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect();
        $announcement = ContentEntry::where('type', 'announcement')->firstOrFail();
        $this->assertSame($teacher->id, $announcement->author_id);
        $this->assertSame($teacher->name, $announcement->author_name);

        $this->actingAs($admin)->get(route('admin.articles.index'))->assertOk()->assertSee('Kegiatan Literasi Sekolah')->assertSee($teacher->name);
    }

    public function test_public_information_list_and_article_detail_use_published_database_records(): void
    {
        $author = User::factory()->create(['role' => 'teacher', 'name' => 'Guru Riwayat']);
        $published = Article::create([
            'author_id' => $author->id,
            'author_name' => $author->name,
            'title' => 'Kegiatan Sekolah Bersama',
            'slug' => 'kegiatan-sekolah-bersama',
            'excerpt' => 'Ringkasan kegiatan sekolah.',
            'body' => '<p>Isi informasi publik.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        Article::create([
            'author_id' => $author->id,
            'author_name' => $author->name,
            'title' => 'Draf Rahasia',
            'slug' => 'draf-rahasia',
            'body' => '<p>Tidak tayang.</p>',
            'status' => 'draft',
        ]);
        Article::create([
            'author_id' => $author->id,
            'author_name' => $author->name,
            'title' => 'Artikel Terjadwal',
            'slug' => 'artikel-terjadwal',
            'body' => '<p>Belum tayang.</p>',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $listing = $this->get(route('information.articles'))->assertOk()
            ->assertSee('Kegiatan Sekolah Bersama')
            ->assertSee('article-card-placeholder')
            ->assertDontSee('Draf Rahasia')
            ->assertDontSee('Artikel Terjadwal');
        preg_match('/<div class="nav-links"[^>]*>(.*?)<\/div>/s', $listing->getContent(), $navigation);
        $this->assertNotEmpty($navigation);
        $this->assertLessThan(strpos($navigation[1], '>Artikel<'), strpos($navigation[1], '>Galeri<'));
        $this->assertLessThan(strpos($navigation[1], '>Kontak<'), strpos($navigation[1], '>Artikel<'));

        $this->get(route('information.article', $published->slug))->assertOk()
            ->assertSee('Isi informasi publik.')
            ->assertSee('Guru Riwayat')
            ->assertSee('Kembali ke Informasi');
    }

    public function test_admin_can_delete_teacher_without_deleting_authored_content_or_other_locks(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Guru Dihapus', 'email' => 'hapus@example.test']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $article = Article::create([
            'author_id' => $teacher->id,
            'author_name' => $teacher->name,
            'updated_by' => $teacher->id,
            'updated_by_name' => $teacher->name,
            'title' => 'Artikel Guru Dihapus',
            'slug' => 'artikel-guru-dihapus',
            'body' => '<p>Konten tetap ada.</p>',
            'cover_image' => 'media/teacher-cover.jpg',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $entry = ContentEntry::create([
            'author_id' => $teacher->id,
            'author_name' => $teacher->name,
            'type' => 'gallery',
            'title' => 'Foto Guru',
            'image_path' => 'media/teacher-gallery.jpg',
            'published' => true,
        ]);
        $locks = app(AccountSessionLockManager::class);
        $this->assertTrue($locks->acquire($teacher->id, 'teacher-delete-lock'));
        $this->assertTrue($locks->acquire($otherTeacher->id, 'other-teacher-lock'));
        $this->actingAs($admin);

        $this->delete(route('admin.users.destroy', $teacher))
            ->assertRedirect()
            ->assertSessionHas('success', 'Akun Guru berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'author_id' => null, 'author_name' => 'Guru Dihapus', 'cover_image' => 'media/teacher-cover.jpg']);
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'updated_by' => null, 'updated_by_name' => 'Guru Dihapus']);
        $this->assertDatabaseHas('content_entries', ['id' => $entry->id, 'author_id' => null, 'author_name' => 'Guru Dihapus']);
        $this->assertDatabaseMissing('account_session_locks', ['user_id' => $teacher->id]);
        $this->assertDatabaseHas('account_session_locks', ['user_id' => $otherTeacher->id, 'session_id' => 'other-teacher-lock']);

        $this->delete(route('admin.users.destroy', $admin))->assertNotFound();
    }

    public function test_teacher_can_edit_and_delete_another_teachers_article_without_changing_original_author(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $article = Article::create([
            'author_id' => $otherTeacher->id,
            'author_name' => $otherTeacher->name,
            'title' => 'Artikel Milik Guru Lain',
            'slug' => 'artikel-guru-lain',
            'body' => '<p>Konten.</p>',
            'status' => 'draft',
        ]);

        $this->actingAs($teacher);
        $this->delete(route('admin.users.destroy', $otherTeacher))->assertForbidden();
        $this->get(route('guru.articles.edit', $article))->assertOk();
        $this->put(route('guru.articles.update', $article), [
            'title' => 'Artikel Diperbarui Guru Lain',
            'slug' => 'artikel-diperbarui-guru-lain',
            'excerpt' => 'Diperbarui bersama.',
            'body' => '<p>Isi diperbarui.</p>',
            'intent' => 'review',
        ])->assertRedirect();
        $article->refresh();
        $this->assertSame($otherTeacher->id, $article->author_id);
        $this->assertSame($otherTeacher->name, $article->author_name);
        $this->assertSame($teacher->id, $article->updated_by);
        $this->delete(route('guru.articles.destroy', $article))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $otherTeacher->id]);
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }
}
