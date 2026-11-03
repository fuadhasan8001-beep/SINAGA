<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\User;
use Tests\TestCase;

class AcademicExtracurricularExperienceTest extends TestCase
{
    public function test_homepage_carousel_and_extracurricular_cards_link_to_published_details_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $learning = ContentEntry::create([
            'author_id' => $admin->id, 'type' => 'learning_activity', 'title' => 'Kegiatan Literasi',
            'slug' => 'kegiatan-literasi-test', 'excerpt' => 'Ringkasan literasi.', 'body' => '<p>Deskripsi literasi.</p>',
            'image_path' => 'media/literasi.jpg', 'published' => true, 'sort_order' => 1,
        ]);
        ContentEntry::create([
            'author_id' => $admin->id, 'type' => 'learning_activity', 'title' => 'Praktik Sains',
            'slug' => 'praktik-sains-test', 'excerpt' => 'Ringkasan sains.', 'published' => true, 'sort_order' => 2,
        ]);
        foreach ([
            ['education_calendar', 'Kalender Pendidikan', 'kalender-pendidikan-test'],
            ['academic_program', 'Program Ekstrakurikuler', 'program-ekstrakurikuler-test'],
            ['academic_assessment', 'Informasi Asesmen Akademik', 'asesmen-akademik-test'],
        ] as [$type, $title, $slug]) {
            ContentEntry::create([
                'author_id' => $admin->id, 'type' => $type, 'title' => $title, 'slug' => $slug,
                'excerpt' => 'Ringkasan kategori tersimpan.', 'body' => '<p>Informasi detail kategori.</p>',
                'published' => true, 'sort_order' => 1,
            ]);
        }
        ContentEntry::create([
            'author_id' => $admin->id, 'type' => 'learning_activity', 'title' => 'Materi Draf',
            'slug' => 'materi-draf-test', 'published' => false, 'sort_order' => 2,
        ]);
        $club = ContentEntry::create([
            'author_id' => $admin->id, 'type' => 'extracurricular', 'title' => 'Pramuka',
            'slug' => 'pramuka-test', 'excerpt' => 'Ringkasan pramuka.', 'body' => '<p>Deskripsi pramuka.</p>',
            'image_path' => 'media/pramuka.jpg', 'published' => true, 'sort_order' => 1,
        ]);
        ContentEntry::create([
            'author_id' => $admin->id, 'type' => 'extracurricular', 'title' => 'Ekstra Draf',
            'slug' => 'ekstra-draf-test', 'excerpt' => 'Tidak dipublikasikan.', 'published' => false,
        ]);

        $homeResponse = $this->get(route('home'));
        $homeResponse->assertOk()
            ->assertSee('data-activity-carousel', false)
            ->assertSee('data-academic-prev', false)->assertSee('data-academic-next', false)
            ->assertSee('aria-label="Kategori akademik"', false)
            ->assertSee('Kalender Pendidikan')->assertSee('Program Ekstrakurikuler')
            ->assertSee('Informasi Asesmen Akademik')
            ->assertSee('Kegiatan Literasi')
            ->assertSee('id="academicDetailModal"', false)
            ->assertSee('Ringkasan literasi.')
            ->assertSee(route('extracurriculars.show', $club->slug), false)
            ->assertSee('Ekstrakurikuler')
            ->assertDontSee('Lihat Semua')
            ->assertDontSee('extracurricular-card-arrow', false)
            ->assertDontSee('activity-card-indicator', false)
            ->assertDontSee('Materi Draf')
            ->assertDontSee('Ekstra Draf');
        preg_match_all('/<button class="learning-activity-card".*?<\/button>/s', $homeResponse->getContent(), $academicCards);
        $this->assertCount(5, $academicCards[0]);
        foreach ($academicCards[0] as $cardHtml) {
            $this->assertStringNotContainsString('Ringkasan', $cardHtml);
            $this->assertStringNotContainsString('Lihat Detail', $cardHtml);
        }

        $this->get(route('learning-activities.show', $learning->slug))->assertOk()
            ->assertSee('Kegiatan Literasi')->assertSee('Foto kegiatan pembelajaran', false)
            ->assertSee('Deskripsi literasi.');
        $this->get(route('extracurriculars.show', $club->slug))->assertOk()
            ->assertSee('Pramuka')->assertSee('Deskripsi pramuka.');
        $this->get(route('extracurriculars.show', 'ekstra-draf-test'))->assertNotFound();
        $this->get(route('learning-activities.show', 'slug-tidak-ada'))->assertNotFound();

        $adminHome = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $adminHome->assertSee(route('admin.content.edit', ['extracurricular', $club]), false)
            ->assertSee(route('admin.dashboard').'#beranda', false);
        $adminPublicDetail = $this->get(route('extracurriculars.show', $club->slug))->assertOk();
        $adminPublicDetail->assertSee(route('home').'#beranda', false)
            ->assertSee(route('home').'#ekstrakurikuler', false)
            ->assertDontSee('admin-sidebar');
    }

    public function test_admin_can_manage_learning_activity_detail_and_extracurricular_order_and_publication(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin)->get(route('admin.content.create', 'learning_activity'))->assertOk()
            ->assertSee('name="title"', false)->assertSee('name="body"', false)
            ->assertSee('name="sort_order"', false)->assertSee('name="published"', false);

        $this->post(route('admin.content.store', 'learning_activity'), [
            'title' => 'Praktik Sains', 'excerpt' => 'Ringkasan praktik sains.',
            'body' => '<p>Deskripsi praktik sains.</p>', 'sort_order' => 2, 'published' => 0,
        ])->assertRedirect(route('admin.settings.edit', ['section' => 'academic', 'edit' => 1]));

        $learning = ContentEntry::where('type', 'learning_activity')->where('title', 'Praktik Sains')->firstOrFail();
        $this->assertSame(2, $learning->sort_order);
        $this->assertFalse($learning->published);
        $this->get(route('learning-activities.show', $learning->slug))->assertNotFound();

        $this->get(route('admin.content.create', 'extracurricular'))->assertOk()
            ->assertSee('name="body"', false)->assertSee('name="sort_order"', false)->assertSee('name="published"', false);
        $this->post(route('admin.content.store', 'extracurricular'), [
            'title' => 'Klub Seni', 'excerpt' => 'Ringkasan klub seni.',
            'body' => '<p>Deskripsi klub seni.</p>', 'sort_order' => 1, 'published' => 1,
        ])->assertRedirect(route('admin.content.index', 'extracurricular'));

        $club = ContentEntry::where('type', 'extracurricular')->where('title', 'Klub Seni')->firstOrFail();
        $this->assertStringContainsString('Deskripsi klub seni.', $club->body);
        $this->get(route('extracurriculars.show', $club->slug))->assertOk()->assertSee('Deskripsi klub seni.');
    }
}
