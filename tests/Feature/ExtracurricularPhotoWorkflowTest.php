<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\ContentEntryPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtracurricularPhotoWorkflowTest extends TestCase
{
    public function test_teacher_can_create_and_edit_an_extracurricular_with_multiple_photos_without_losing_legacy_photo(): void
    {
        Storage::fake('public');
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);

        $this->actingAs($teacher)->get(route('guru.content.create', 'extracurricular'))
            ->assertOk()
            ->assertSee('name="photos[]"', false)
            ->assertSee('extracurricularPhotosCount', false);

        $this->post(route('guru.content.store', 'extracurricular'), [
            'title' => 'Klub Sains',
            'excerpt' => 'Kegiatan eksperimen dan penelitian siswa.',
            'body' => '<p>Deskripsi kegiatan yang cukup lengkap.</p>',
            'published' => '1',
            'image' => UploadedFile::fake()->image('utama.jpg', 900, 600),
            'photos' => [
                UploadedFile::fake()->image('praktik-1.jpg', 700, 500),
                UploadedFile::fake()->image('praktik-2.png', 700, 500),
            ],
        ])->assertRedirect(route('guru.content.index', 'extracurricular'));

        $entry = ContentEntry::where('type', 'extracurricular')->firstOrFail();
        $this->get(route('guru.content.index', 'extracurricular'))
            ->assertOk()
            ->assertSee('Tambah Ekstrakurikuler')
            ->assertSee(route('guru.content.edit', ['extracurricular', $entry]), false);
        $legacyImage = $entry->image_path;
        $this->assertNotEmpty($legacyImage);
        $this->assertSame(2, $entry->photos()->count());
        Storage::disk('public')->assertExists($legacyImage);
        foreach ($entry->photos as $photo) Storage::disk('public')->assertExists($photo->path);

        $this->put(route('guru.content.update', ['extracurricular', $entry]), [
            'title' => 'Klub Sains SMP',
            'excerpt' => 'Kegiatan eksperimen dan penelitian siswa.',
            'body' => '<p>Deskripsi kegiatan yang cukup lengkap.</p>',
            'published' => '1',
            'photos' => [UploadedFile::fake()->image('praktik-3.webp', 700, 500)],
        ])->assertRedirect(route('guru.content.index', 'extracurricular'));

        $entry->refresh();
        $this->assertDatabaseCount('content_entries', 1);
        $this->assertSame($legacyImage, $entry->image_path);
        $this->assertSame(3, $entry->photos()->count());
        Storage::disk('public')->assertExists($legacyImage);

        $photo = $entry->photos()->firstOrFail();
        $photoPath = $photo->path;
        $this->delete(route('guru.content.photos.destroy', [$entry, $photo]))->assertRedirect();
        $this->assertDatabaseMissing('content_entry_photos', ['id' => $photo->id]);
        $this->assertSame(2, $entry->photos()->count());
        Storage::disk('public')->assertMissing($photoPath);
        Storage::disk('public')->assertExists($legacyImage);

        $this->get(route('extracurriculars.show', $entry->slug))
            ->assertOk()
            ->assertSee('extracurricular-detail-layout', false)
            ->assertSee('activity-detail-copy--extracurricular', false)
            ->assertSee('Klub Sains SMP')
            ->assertSee('Foto lainnya dari Klub Sains SMP', false);
    }

    public function test_teacher_can_see_and_edit_existing_extracurricular_entries_but_not_admin_settings(): void
    {
        $owner = User::factory()->create(['role' => 'admin', 'active' => true]);
        $otherTeacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $entry = ContentEntry::create([
            'author_id' => $owner->id,
            'author_name' => $owner->name,
            'type' => 'extracurricular',
            'title' => 'Klub Pemilik',
            'slug' => 'klub-pemilik',
            'excerpt' => 'Kegiatan pemilik.',
            'published' => true,
        ]);

        $this->actingAs($otherTeacher)
            ->get(route('guru.content.edit', ['extracurricular', $entry]))
            ->assertOk()
            ->assertSee('Klub Pemilik');
        $this->get(route('guru.content.index', 'extracurricular'))
            ->assertOk()
            ->assertSee('Klub Pemilik');
        $this->put(route('guru.content.update', ['extracurricular', $entry]), [
            'title' => 'Klub Pemilik Diperbarui',
            'excerpt' => 'Kegiatan pemilik yang diperbarui.',
            'published' => '1',
        ])->assertRedirect(route('guru.content.index', 'extracurricular'));
        $this->assertDatabaseHas('content_entries', ['id' => $entry->id, 'title' => 'Klub Pemilik Diperbarui']);
        $this->get('/guru/spmb')->assertNotFound();
        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_stale_admin_extracurricular_create_link_redirects_to_the_guru_panel(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);

        $this->actingAs($teacher)->get('/admin/content/extracurricular/create')
            ->assertRedirect('/guru/content/extracurricular/create');
        $this->get('/guru/content/extracurricular/create')->assertOk();
        $this->get('/admin/content/achievement')->assertForbidden();
    }
}
