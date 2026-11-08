<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicHomepageRefinementTest extends TestCase
{
    public function test_homepage_displays_school_service_hours_as_individual_days(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('Senin – Kamis', $html);
        $this->assertStringContainsString('07.30–13.30', $html);
        $this->assertStringContainsString('Jumat', $html);
        $this->assertStringContainsString('07.30–11.00', $html);
        $this->assertStringContainsString('Sabtu', $html);
        $this->assertStringContainsString('07.30–12.00', $html);
        $this->assertStringContainsString('Minggu', $html);
        $this->assertStringContainsString('Tutup', $html);
    }

    public function test_homepage_shows_a_compact_empty_state_when_there_are_no_published_notices(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        ContentEntry::create([
            'author_id' => $admin->id,
            'type' => 'announcement',
            'title' => 'Pengumuman draf',
            'slug' => 'pengumuman-draf',
            'published' => false,
        ]);

        $this->get(route('home'))->assertOk()
            ->assertSee('Belum ada pengumuman terbaru')
            ->assertSee('notice-grid--single', false)
            ->assertDontSee('Pengumuman draf')
            ->assertDontSee('Lihat semua pengumuman dan agenda');
    }

    public function test_homepage_shows_only_the_five_latest_published_announcements_without_a_list_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        foreach (range(1, 7) as $day) {
            ContentEntry::create([
                'author_id' => $admin->id,
                'type' => 'announcement',
                'title' => 'Pengumuman '.$day,
                'slug' => 'pengumuman-'.$day,
                'excerpt' => 'Ringkasan '.$day,
                'starts_at' => Carbon::parse('2026-10-'.$day),
                'published' => true,
            ]);
        }
        ContentEntry::create([
            'author_id' => $admin->id,
            'type' => 'announcement',
            'title' => 'Pengumuman Belum Tayang',
            'slug' => 'pengumuman-belum-tayang',
            'starts_at' => Carbon::parse('2026-11-01'),
            'published' => false,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();
        foreach (range(3, 7) as $day) $this->assertStringContainsString('Pengumuman '.$day, $html);
        foreach (range(1, 2) as $day) $this->assertStringNotContainsString('Pengumuman '.$day.'</h3>', $html);
        $this->assertStringNotContainsString('Pengumuman Belum Tayang', $html);
        $this->assertStringNotContainsString('Lihat semua pengumuman dan agenda', $html);
        $this->assertLessThan(strpos($html, 'Pengumuman 6'), strpos($html, 'Pengumuman 7'));
    }

    public function test_hero_uses_only_its_dedicated_photo_setting_and_never_falls_back_to_gallery(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $galleryPhoto = ContentEntry::create([
            'author_id' => $admin->id,
            'type' => 'gallery',
            'title' => 'Foto Galeri Teratas',
            'slug' => 'foto-galeri-teratas',
            'image_path' => 'media/gallery-only.jpg',
            'published' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)->get(route('admin.photos.edit', 'hero'))->assertOk()
            ->assertSee('Belum ada foto untuk area ini.')
            ->assertDontSee(Storage::disk('public')->url($galleryPhoto->image_path), false);

        $html = $this->get(route('home'))->assertOk()->getContent();
        $heroEnd = strpos($html, '</section>');
        $heroMarkup = substr($html, 0, $heroEnd ?: strlen($html));
        $this->assertStringNotContainsString(Storage::disk('public')->url($galleryPhoto->image_path), $heroMarkup);
        $this->assertStringContainsString(Storage::disk('public')->url($galleryPhoto->image_path), $html);
        $this->assertStringNotContainsString('hero-photo-secondary', $html);

        SiteSetting::create(['key' => 'hero_photo_path', 'value' => json_encode('school-photos/beranda.jpg')]);
        $html = $this->get(route('home'))->assertOk()->getContent();
        $heroEnd = strpos($html, '</section>');
        $heroMarkup = substr($html, 0, $heroEnd ?: strlen($html));
        $this->assertStringContainsString(Storage::disk('public')->url('school-photos/beranda.jpg'), $heroMarkup);
        $this->assertStringNotContainsString(Storage::disk('public')->url($galleryPhoto->image_path), $heroMarkup);

        $this->get(route('admin.photos.edit', 'hero'))->assertOk()
            ->assertSee(Storage::disk('public')->url('school-photos/beranda.jpg'), false);
    }
}
