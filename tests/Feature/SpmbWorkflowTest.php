<?php

namespace Tests\Feature;

use App\Models\SpmbSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpmbWorkflowTest extends TestCase
{
    public function test_spmb_admin_page_only_contains_the_three_requested_edit_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);

        $this->get(route('admin.spmb.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($teacher)->get(route('admin.spmb.dashboard'))->assertForbidden();

        $response = $this->actingAs($admin)->get(route('admin.spmb.dashboard'))
            ->assertOk()
            ->assertSee('Foto poster')
            ->assertSee('Keterangan tambahan')
            ->assertSee('WhatsApp Contact Person')
            ->assertSee('name="poster"', false)
            ->assertSee('name="introduction"', false)
            ->assertSee('name="contact_whatsapp"', false)
            ->assertSee('Simpan dan terbitkan')
            ->assertDontSee('Draf')
            ->assertDontSee('Jadwal pendaftaran')
            ->assertDontSee('Tautan penting')
            ->assertDontSee('Pengumuman SPMB')
            ->assertDontSee('Persyaratan SPMB');

        $this->assertSame(1, substr_count($response->getContent(), 'type="file"'));
        $this->assertSame(1, substr_count($response->getContent(), 'name="introduction"'));
        $this->get('/admin/spmb/information')->assertNotFound();
        $this->get('/admin/spmb/links')->assertNotFound();
        $this->get('/admin/spmb/contacts')->assertNotFound();
    }

    public function test_admin_changes_publish_immediately_to_guest_and_logged_in_visitor_views(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $existingPoster = 'spmb/posters/old-poster.jpg';
        Storage::disk('public')->put($existingPoster, 'old poster');
        SpmbSetting::create([
            'title' => 'Informasi SPMB lama',
            'poster_path' => $existingPoster,
            'introduction' => 'Keterangan lama',
            'contact_whatsapp' => '6281111111111',
            'published' => false,
        ]);

        $this->actingAs($admin)->put(route('admin.spmb.dashboard.update'), [
            'poster' => UploadedFile::fake()->image('poster-baru.png', 900, 1200),
            'introduction' => "Pendaftaran tahun ini dibuka.\nSilakan siapkan berkas.",
            'contact_whatsapp' => '6281234567890',
        ])->assertRedirect()->assertSessionHas('success');

        $settings = SpmbSetting::query()->findOrFail(1);
        $this->assertTrue($settings->published);
        $this->assertSame("Pendaftaran tahun ini dibuka.\nSilakan siapkan berkas.", $settings->introduction);
        $this->assertSame('6281234567890', $settings->contact_whatsapp);
        $this->assertNotSame($existingPoster, $settings->poster_path);
        Storage::disk('public')->assertExists($settings->poster_path);
        Storage::disk('public')->assertMissing($existingPoster);

        $guestPage = $this->get(route('spmb.public'))
            ->assertOk()
            ->assertHeaderContains('Cache-Control', 'no-store')
            ->assertSee(Storage::disk('public')->url($settings->poster_path), false)
            ->assertSee('Keterangan tambahan')
            ->assertSee('Pendaftaran tahun ini dibuka.')
            ->assertSee('Silakan siapkan berkas.')
            ->assertSee('Contact Person')
            ->assertSee('https://wa.me/6281234567890')
            ->assertDontSee('Jadwal pendaftaran')
            ->assertDontSee('Dokumentasi')
            ->assertDontSee('Tautan penting')
            ->assertDontSee('admin-sidebar');

        $this->assertLessThan(strpos($guestPage->getContent(), 'Keterangan tambahan'), strpos($guestPage->getContent(), 'Poster utama SPMB'));
        $this->assertLessThan(strpos($guestPage->getContent(), 'Contact Person'), strpos($guestPage->getContent(), 'Keterangan tambahan'));

        $this->actingAs($teacher)->get(route('spmb.public'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($settings->poster_path), false)
            ->assertSee('Pendaftaran tahun ini dibuka.')
            ->assertSee('https://wa.me/6281234567890');

        $this->actingAs($admin)->put(route('admin.spmb.dashboard.update'), [
            'introduction' => 'Informasi terbaru setelah disimpan ulang.',
            'contact_whatsapp' => '',
        ])->assertRedirect();
        $this->get(route('spmb.public'))->assertOk()
            ->assertSee('Informasi terbaru setelah disimpan ulang.')
            ->assertDontSee('https://wa.me/6281234567890')
            ->assertSee('Nomor WhatsApp Contact Person belum tersedia.');
    }

    public function test_invalid_poster_or_whatsapp_does_not_replace_saved_information(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $oldPoster = UploadedFile::fake()->image('poster-lama.jpg');
        $path = $oldPoster->storePublicly('spmb/posters', 'public');
        SpmbSetting::create([
            'poster_path' => $path,
            'introduction' => 'Keterangan tersimpan',
            'contact_whatsapp' => '6281234567890',
            'published' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.spmb.dashboard.update'), [
            'poster' => UploadedFile::fake()->create('bukan-gambar.txt', 10, 'text/plain'),
            'introduction' => 'Keterangan baru',
            'contact_whatsapp' => '+6281234567890',
        ])->assertSessionHasErrors(['poster', 'contact_whatsapp']);

        $settings = SpmbSetting::query()->findOrFail(1);
        $this->assertSame($path, $settings->poster_path);
        $this->assertSame('Keterangan tersimpan', $settings->introduction);
        $this->assertSame('6281234567890', $settings->contact_whatsapp);
        Storage::disk('public')->assertExists($path);
    }
}
