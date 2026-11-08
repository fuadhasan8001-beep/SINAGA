<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\ContentEntry;
use App\Models\User;
use App\Services\AccountSessionLockManager;
use Illuminate\Support\Carbon;
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
            ->assertSee(route('admin.content.index', 'announcement'), false)
            ->assertSee('Mode pengunjung');
        $this->get(route('home'))->assertOk()
            ->assertSee(route('admin.dashboard'), false)
            ->assertSee('Kembali ke Admin');
        $this->get(route('admin.articles.index'))->assertOk();
        $this->get(route('admin.content.index', 'gallery'))->assertOk();
        $this->get(route('admin.content.index', 'announcement'))->assertOk();
        $this->get(route('guru.dashboard'))->assertForbidden();

        auth()->logout();
        $guruDashboard = $this->actingAs($teacher)->get(route('guru.dashboard'))->assertOk()->assertViewIs('public.home');
        $guruDashboard->assertSee(route('guru.articles.index'), false)
            ->assertSee(route('guru.content.index', 'gallery'), false)
            ->assertSee(route('guru.content.index', 'announcement'), false)
            ->assertSee('Mode pengunjung');
        $this->get(route('home'))->assertOk()
            ->assertSee(route('guru.dashboard'), false)
            ->assertSee('Kembali ke Guru');
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
        $this->assertStringContainsString('Mode pengunjung', $sidebar[1]);

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
            ->assertDontSee('Guru Riwayat')
            ->assertSee('Kembali ke Informasi');
    }

    public function test_public_articles_use_the_school_timezone_when_checking_publication_time(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));

        $author = User::factory()->create(['role' => 'admin', 'active' => true]);
        $publishedAt = now()->subMinute();

        $first = Article::create([
            'author_id' => $author->id,
            'author_name' => $author->name,
            'title' => 'Artikel WIB Pertama',
            'slug' => 'artikel-wib-pertama',
            'body' => '<p>Isi artikel pertama.</p>',
            'status' => 'published',
            'published_at' => $publishedAt,
        ]);
        $second = Article::create([
            'author_id' => $author->id,
            'author_name' => $author->name,
            'title' => 'Artikel WIB Kedua',
            'slug' => 'artikel-wib-kedua',
            'body' => '<p>Isi artikel kedua.</p>',
            'status' => 'published',
            'published_at' => $publishedAt->copy()->addSecond(),
        ]);

        $this->get(route('information.articles'))->assertOk()
            ->assertSee($first->title)
            ->assertSee($second->title)
            ->assertSee(route('information.article', $first->slug), false)
            ->assertSee(route('information.article', $second->slug), false);
        $this->get(route('information.article', $first->slug))->assertOk()->assertSee('Isi artikel pertama.');
        $this->get(route('information.article', $second->slug))->assertOk()->assertSee('Isi artikel kedua.');
    }

    public function test_admin_and_teacher_published_articles_keep_their_html_and_each_public_link_loads_its_own_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);

        $adminBody = '<h2>Judul Admin</h2><p>Paragraf khusus admin.</p><ul><li>Butir admin</li></ul><a href="https://example.test">Tautan admin</a><script>alert(1)</script>';
        $teacherBody = '<h3>Judul Guru</h3><p>Paragraf khusus guru.</p><ol><li>Butir guru</li></ol><a href="mailto:guru@example.test">Tautan guru</a>';

        $this->actingAs($admin)->post(route('admin.articles.store'), [
            'title' => 'Artikel Khusus Admin',
            'slug' => 'artikel-khusus-admin',
            'excerpt' => 'Ringkasan admin.',
            'body' => $adminBody,
            'intent' => 'review',
        ])->assertRedirect();

        $this->actingAs($teacher)->post(route('guru.articles.store'), [
            'title' => 'Artikel Khusus Guru',
            'slug' => 'artikel-khusus-guru',
            'excerpt' => 'Ringkasan guru.',
            'body' => $teacherBody,
            'intent' => 'review',
        ])->assertRedirect();

        $adminArticle = Article::where('slug', 'artikel-khusus-admin')->firstOrFail();
        $teacherArticle = Article::where('slug', 'artikel-khusus-guru')->firstOrFail();

        $this->assertSame('published', $adminArticle->status);
        $this->assertSame('published', $teacherArticle->status);
        $this->assertStringContainsString('<h2>Judul Admin</h2>', $adminArticle->body);
        $this->assertStringContainsString('<h3>Judul Guru</h3>', $teacherArticle->body);
        $this->assertStringNotContainsString('<script>', $adminArticle->body);

        $listing = $this->get(route('information.articles'))->assertOk();
        $listing->assertSee(route('information.article', $adminArticle->slug), false)
            ->assertSee(route('information.article', $teacherArticle->slug), false);

        $this->get(route('information.article', $adminArticle->slug))->assertOk()
            ->assertSee('Judul Admin')
            ->assertSee('Paragraf khusus admin.')
            ->assertSee('Butir admin')
            ->assertSee('Tautan admin')
            ->assertDontSee('Paragraf khusus guru.')
            ->assertDontSee('alert(1)');
        $this->get(route('information.article', $teacherArticle->slug))->assertOk()
            ->assertSee('Judul Guru')
            ->assertSee('Paragraf khusus guru.')
            ->assertSee('Butir guru')
            ->assertSee('Tautan guru')
            ->assertDontSee('Paragraf khusus admin.');

        $this->actingAs($admin)->post(route('admin.articles.unpublish', $adminArticle))->assertRedirect();
        $this->get(route('information.article', $adminArticle->slug))->assertNotFound();
    }

    public function test_article_author_metadata_is_hidden_from_visitors_and_visible_in_management_contexts(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true, 'name' => 'Guru Pemilik']);
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $category = Category::create(['name' => 'Berita', 'slug' => 'berita']);
        $article = Article::create([
            'author_id' => $teacher->id,
            'author_name' => $teacher->name,
            'category_id' => $category->id,
            'title' => 'Artikel Metadata',
            'slug' => 'artikel-metadata',
            'body' => '<p>Isi artikel.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $legacyArticle = Article::create([
            'author_id' => null,
            'author_name' => 'Guru Artikel Lama',
            'category_id' => $category->id,
            'title' => 'Artikel Lama Metadata',
            'slug' => 'artikel-lama-metadata',
            'body' => '<p>Isi artikel lama.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $publicDetail = $this->get(route('information.article', $article->slug))->assertOk()
            ->assertSee('Berita')
            ->assertDontSee('Guru Pemilik');
        preg_match('/<p class="article-meta">(.*?)<\/p>/s', $publicDetail->getContent(), $metadata);
        $this->assertNotEmpty($metadata);
        $metadataText = trim(preg_replace('/\s+/', ' ', strip_tags($metadata[1])));
        $this->assertSame($article->published_at->timezone('Asia/Jakarta')->translatedFormat('d F Y').' · Berita', $metadataText);

        $this->actingAs($teacher)->get(route('guru.articles.index'))->assertOk()->assertSee('Guru Pemilik');
        $this->get(route('information.article', $article->slug))->assertOk()->assertDontSee('Guru Pemilik');
        $this->get(route('guru.articles.edit', $article))->assertOk()->assertSee('Diunggah oleh: Guru Pemilik');
        $this->actingAs($admin)->get(route('admin.articles.index'))->assertOk()
            ->assertSee('Guru Pemilik')->assertSee('Guru Artikel Lama');
        $this->get(route('admin.website.article', $article->slug))->assertOk()->assertDontSee('Guru Pemilik');
        $this->get(route('admin.articles.preview', $article))->assertOk()->assertSee('Guru Pemilik');
        $this->get(route('admin.articles.edit', $legacyArticle))->assertOk()->assertSee('Diunggah oleh: Guru Artikel Lama');

        auth()->logout();
        $this->get(route('information.article', $legacyArticle->slug))->assertOk()
            ->assertSee('Berita')->assertDontSee('Guru Artikel Lama');
    }

    public function test_scheduled_article_uses_wib_and_becomes_public_at_its_time_without_manual_action(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 01:00:00', 'UTC'));
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $this->actingAs($teacher)->post(route('guru.articles.store'), [
            'title' => 'Artikel Terjadwal WIB',
            'slug' => 'artikel-terjadwal-wib',
            'body' => '<p>Isi terjadwal.</p>',
            'published_at' => '2026-11-06T08:10',
            'intent' => 'review',
        ])->assertRedirect();

        $article = Article::where('slug', 'artikel-terjadwal-wib')->firstOrFail();
        $this->assertSame('2026-11-06 01:10:00', $article->published_at->copy()->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('08:10', $article->published_at->copy()->timezone('Asia/Jakarta')->format('H:i'));
        $this->get(route('guru.articles.index'))->assertOk()->assertSee('Artikel Terjadwal WIB');
        $this->get(route('information.articles'))->assertDontSee('Artikel Terjadwal WIB');
        $this->get(route('information.article', $article->slug))->assertNotFound();

        // The author can change the schedule while it remains in the future.
        $this->put(route('guru.articles.update', $article), [
            'title' => 'Artikel Terjadwal WIB',
            'slug' => 'artikel-terjadwal-wib',
            'body' => '<p>Isi terjadwal.</p>',
            'published_at' => '2026-11-06T08:01',
            'intent' => 'review',
            'status' => 'published',
        ])->assertRedirect();
        $article->refresh();
        $this->assertSame('2026-11-06 01:01:00', $article->published_at->copy()->utc()->format('Y-m-d H:i:s'));

        Carbon::setTestNow(Carbon::parse('2026-11-06 01:00:59', 'UTC'));
        $this->get(route('information.articles'))->assertDontSee('Artikel Terjadwal WIB');
        Carbon::setTestNow(Carbon::parse('2026-11-06 01:01:00', 'UTC'));
        $this->get(route('information.articles'))->assertSee('Artikel Terjadwal WIB');
        $this->get(route('information.article', $article->slug))->assertOk()->assertSee('Isi terjadwal.');
        Carbon::setTestNow();
    }

    public function test_article_scheduled_in_the_past_is_public_immediately(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-06 01:00:00', 'UTC'));
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);

        $this->actingAs($teacher)->post(route('guru.articles.store'), [
            'title' => 'Artikel Jadwal Lampau',
            'slug' => 'artikel-jadwal-lampau',
            'body' => '<p>Sudah waktunya terbit.</p>',
            'published_at' => '2026-11-06T07:59',
            'intent' => 'review',
        ])->assertRedirect();

        $article = Article::where('slug', 'artikel-jadwal-lampau')->firstOrFail();
        $this->assertSame('published', $article->status);
        $this->get(route('information.articles'))->assertSee($article->title);
        $this->get(route('information.article', $article->slug))->assertOk()->assertSee('Sudah waktunya terbit.');
        Carbon::setTestNow();
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

    public function test_teacher_cannot_access_another_teachers_article_but_admin_can_manage_it(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        Article::create([
            'author_id' => $teacher->id,
            'author_name' => $teacher->name,
            'title' => 'Artikel Milik Guru A',
            'slug' => 'artikel-guru-a',
            'body' => '<p>Konten A.</p>',
            'status' => 'draft',
        ]);
        $article = Article::create([
            'author_id' => $otherTeacher->id,
            'author_name' => $otherTeacher->name,
            'title' => 'Artikel Milik Guru Lain',
            'slug' => 'artikel-guru-lain',
            'body' => '<p>Konten.</p>',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->actingAs($teacher);
        $this->delete(route('admin.users.destroy', $otherTeacher))->assertForbidden();
        $this->get(route('guru.articles.index'))->assertOk()
            ->assertSee('Artikel Milik Guru A')
            ->assertDontSee('Artikel Milik Guru Lain');
        $this->get(route('guru.articles.edit', $article))->assertForbidden();
        $this->put(route('guru.articles.update', $article), [
            'title' => 'Artikel Diperbarui Guru Lain',
            'slug' => 'artikel-diperbarui-guru-lain',
            'excerpt' => 'Diperbarui bersama.',
            'body' => '<p>Isi diperbarui.</p>',
            'published_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'intent' => 'review',
        ])->assertForbidden();
        $article->refresh();
        $this->assertSame($otherTeacher->id, $article->author_id);
        $this->assertSame($otherTeacher->name, $article->author_name);
        $this->assertNull($article->updated_by);
        $this->assertSame('Artikel Milik Guru Lain', $article->title);
        $this->delete(route('guru.articles.destroy', $article))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $otherTeacher->id]);
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'published_at' => $article->published_at->format('Y-m-d H:i:s')]);

        $this->actingAs($admin)->get(route('admin.articles.edit', $article))->assertOk();
        $this->put(route('admin.articles.update', $article), [
            'title' => 'Artikel Dikelola Admin',
            'slug' => 'artikel-dikelola-admin',
            'body' => '<p>Isi diperbarui Admin.</p>',
            'intent' => 'review',
            'status' => 'published',
        ])->assertRedirect();
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => 'Artikel Dikelola Admin']);
    }
}
