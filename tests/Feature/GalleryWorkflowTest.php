<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryWorkflowTest extends TestCase
{
    public function test_gallery_form_only_exposes_title_photo_and_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin);

        $this->get(route('admin.content.create', 'gallery'))->assertOk()
            ->assertSee('Masukkan judul foto')
            ->assertSee('Pilih atau unggah foto')
            ->assertSee('name="sort_order"', false)
            ->assertDontSee('name="excerpt"', false)
            ->assertDontSee('name="body"', false)
            ->assertDontSee('metadata_json', false)
            ->assertDontSee('name="published"', false);

        $this->get(route('admin.content.index', 'gallery'))->assertOk()
            ->assertSee('Kelola Galeri')
            ->assertSee('Tambah Foto')
            ->assertSee('Lihat Galeri')
            ->assertSee(route('home').'#galeri', false)
            ->assertSee('Urutan ini digunakan pada carousel publik.')
            ->assertSee('galleryAdminGrid', false)
            ->assertSee('Galeri belum memiliki foto');
    }

    public function test_gallery_upload_update_order_and_delete_keep_storage_consistent(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin);

        $this->post(route('admin.content.store', 'gallery'), [
            'title' => 'Pentas Seni',
            'sort_order' => 0,
            'image' => UploadedFile::fake()->image('pentas.jpg', 640, 480),
        ])->assertRedirect(route('admin.content.index', 'gallery'));

        $first = ContentEntry::where('type', 'gallery')->firstOrFail();
        $firstImage = $first->image_path;
        $this->assertTrue(Storage::disk('public')->exists($firstImage));
        $this->assertTrue($first->published);

        $this->put(route('admin.content.update', ['gallery', $first]), [
            'title' => 'Pentas Seni Tahunan',
            'sort_order' => 4,
        ])->assertRedirect(route('admin.content.index', 'gallery'));

        $first->refresh();
        $this->assertSame('Pentas Seni Tahunan', $first->title);
        $this->assertSame($firstImage, $first->image_path);
        $this->assertTrue(Storage::disk('public')->exists($firstImage));

        $second = ContentEntry::create([
            'author_id' => $admin->id,
            'type' => 'gallery',
            'title' => 'Upacara',
            'slug' => 'upacara',
            'image_path' => $firstImage,
            'published' => true,
            'sort_order' => 1,
        ]);

        $this->patchJson(route('admin.content.gallery.reorder'), [
            'ordered_ids' => [$second->id, $first->id],
        ])->assertOk()->assertJson(['message' => 'Urutan galeri tersimpan.']);
        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);

        $this->delete(route('admin.content.destroy', ['gallery', $second]))->assertRedirect();
        $this->assertTrue(Storage::disk('public')->exists($firstImage), 'A shared image must remain until its final gallery entry is removed.');

        $this->put(route('admin.content.update', ['gallery', $first]), [
            'title' => $first->title,
            'sort_order' => $first->sort_order,
            'image' => UploadedFile::fake()->image('pentas-baru.webp', 640, 480),
        ])->assertRedirect(route('admin.content.index', 'gallery'));
        $this->assertFalse(Storage::disk('public')->exists($firstImage));
        $this->assertTrue(Storage::disk('public')->exists($first->fresh()->image_path));
    }

    public function test_public_gallery_shows_ordered_cards_and_navigable_lightbox_without_empty_state_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        ContentEntry::create(['author_id' => $admin->id, 'type' => 'gallery', 'title' => 'Foto Kedua', 'slug' => 'foto-kedua', 'image_path' => 'media/kedua.jpg', 'published' => true, 'sort_order' => 1]);
        ContentEntry::create(['author_id' => $admin->id, 'type' => 'gallery', 'title' => 'Foto Pertama', 'slug' => 'foto-pertama', 'image_path' => 'media/pertama.jpg', 'published' => true, 'sort_order' => 0]);

        $response = $this->get(route('home'))->assertOk()
            ->assertSee('Galeri Sekolah')
            ->assertSee('Dokumentasi Kegiatan Sekolah')
            ->assertSee('Foto dokumentasi yang telah diizinkan untuk dipublikasikan.')
            ->assertSee('id="publicGalleryViewport"', false)
            ->assertSee('data-gallery-carousel-prev', false)
            ->assertSee('data-gallery-carousel-next', false)
            ->assertSee('data-gallery-prev', false)
            ->assertSee('data-gallery-next', false)
            ->assertSee('galleryLightboxTitle')
            ->assertDontSee('＋ Tambah Foto');

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'Foto Kedua'), strpos($html, 'Foto Pertama'));

        ContentEntry::where('type', 'gallery')->delete();
        $this->get(route('home'))->assertOk()->assertSee('Galeri belum memiliki dokumentasi')
            ->assertDontSee('data-gallery-carousel-prev', false)
            ->assertDontSee('data-gallery-carousel-next', false)
            ->assertDontSee('Tambah Foto');
    }
}
