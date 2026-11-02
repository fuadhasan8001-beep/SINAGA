<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnnouncementWorkflowTest extends TestCase
{
    public function test_admin_home_links_to_announcement_list_and_form_has_one_description_field(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Kelola Pengumuman')
            ->assertSee(route('admin.content.index', 'announcement'), false)
            ->assertDontSee(route('admin.content.create', 'announcement'), false);

        $this->get(route('admin.content.create', 'announcement'))
            ->assertOk()
            ->assertSee('name="description"', false)
            ->assertSee('Deskripsi Pengumuman')
            ->assertSee('name="sort_order"', false)
            ->assertSee('name="published"', false)
            ->assertDontSee('name="excerpt"', false)
            ->assertDontSee('name="body"', false)
            ->assertDontSee('name="metadata_json"', false);

        $this->post(route('admin.content.store', 'announcement'), [
            'title' => 'Pengumuman Baru',
            'description' => 'Informasi lengkap pada satu kolom deskripsi.',
            'starts_at' => '2026-11-15',
            'published' => 1,
        ])->assertRedirect(route('admin.content.index', 'announcement'));

        $this->assertDatabaseHas('content_entries', [
            'type' => 'announcement',
            'title' => 'Pengumuman Baru',
            'excerpt' => 'Informasi lengkap pada satu kolom deskripsi.',
            'published' => 1,
            'sort_order' => 1,
        ]);
    }

    public function test_announcement_order_controls_public_order_and_description_updates_legacy_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $later = ContentEntry::create([
            'author_id' => $admin->id,
            'type' => 'announcement',
            'title' => 'Urutan Dua',
            'slug' => 'urutan-dua',
            'excerpt' => 'Isi kedua.',
            'starts_at' => Carbon::parse('2026-11-20'),
            'published' => true,
            'sort_order' => 2,
        ]);
        $first = ContentEntry::create([
            'author_id' => $admin->id,
            'type' => 'announcement',
            'title' => 'Urutan Satu',
            'slug' => 'urutan-satu',
            'excerpt' => 'Isi pertama.',
            'starts_at' => Carbon::parse('2026-11-01'),
            'published' => true,
            'sort_order' => 1,
            'metadata' => ['legacy' => 'tetap tersimpan'],
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Urutan Dua'), strpos($html, 'Urutan Satu'));

        $this->actingAs($admin)->put(route('admin.content.update', ['announcement', $later]), [
            'title' => 'Urutan Dua',
            'description' => 'Informasi lengkap yang ditulis pada satu kolom.',
            'starts_at' => '2026-11-20',
            'sort_order' => 0,
            'published' => 1,
        ])->assertRedirect(route('admin.content.index', 'announcement'));

        $later->refresh();
        $this->assertSame('Informasi lengkap yang ditulis pada satu kolom.', $later->excerpt);
        $this->assertStringContainsString('Informasi lengkap yang ditulis pada satu kolom.', $later->body);
        $this->assertSame(0, $later->sort_order);
        $this->assertSame(['legacy' => 'tetap tersimpan'], $first->fresh()->metadata);

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Urutan Satu'), strpos($html, 'Urutan Dua'));

        $this->delete(route('admin.content.destroy', ['announcement', $later]))
            ->assertRedirect(route('admin.content.index', 'announcement'));
        $this->get(route('home'))->assertOk()->assertSee('Urutan Satu')->assertDontSee('Urutan Dua');
    }
}
