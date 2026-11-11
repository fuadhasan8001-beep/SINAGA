<?php

namespace Tests\Feature;

use App\Models\SpmbSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SpmbWorkflowTest extends TestCase
{
    public function test_spmb_admin_page_only_contains_poster_announcement_and_contact_person_inputs(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);

        $this->get(route('admin.spmb.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($teacher)->get(route('admin.spmb.dashboard'))->assertForbidden();

        $response = $this->actingAs($admin)->get(route('admin.spmb.dashboard'))
            ->assertOk()
            ->assertSee('Poster SPMB')
            ->assertSee('Pengumuman')
            ->assertSee('Contact person')
            ->assertSee('name="poster"', false)
            ->assertSee('name="introduction"', false)
            ->assertSee('name="contact_whatsapp"', false)
            ->assertSee('Simpan Perubahan')
            ->assertDontSee('Jadwal pendaftaran')
            ->assertDontSee('Persyaratan pendaftaran')
            ->assertDontSee('Tampilan halaman SPMB')
            ->assertDontSee('Draf')
            ->assertDontSee('Pengumuman SPMB');

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
            'contact_whatsapp' => '0812-3456-7890',
        ])->assertRedirect()->assertSessionHas('success');

        $settings = SpmbSetting::query()->findOrFail(1);
        $this->assertTrue($settings->published);
        $this->assertSame("Pendaftaran tahun ini dibuka.\nSilakan siapkan berkas.", $settings->introduction);
        $this->assertSame('6281234567890', $settings->contact_whatsapp);
        $this->assertSame('Informasi SPMB lama', $settings->title);
        $this->assertNull($settings->schedule);
        $this->assertNotSame($existingPoster, $settings->poster_path);
        Storage::disk('public')->assertExists($settings->poster_path);
        Storage::disk('public')->assertMissing($existingPoster);

        $guestPage = $this->get(route('spmb.public'))
            ->assertOk()
            ->assertHeaderContains('Cache-Control', 'no-store')
            ->assertSee(Storage::disk('public')->url($settings->poster_path), false)
            ->assertSee('Pendaftaran tahun ini dibuka.')
            ->assertSee('Silakan siapkan berkas.')
            ->assertSee('Informasi SPMB lama')
            ->assertDontSee('Jadwal pendaftaran')
            ->assertDontSee('Informasi tambahan')
            ->assertSee('Contact Person')
            ->assertSee('https://wa.me/6281234567890')
            ->assertDontSee('Dokumentasi')
            ->assertDontSee('admin-sidebar');

        $this->actingAs($teacher)->get(route('spmb.public'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($settings->poster_path), false)
            ->assertSee('Pendaftaran tahun ini dibuka.')
            ->assertSee('https://wa.me/6281234567890');

        $this->actingAs($admin)->put(route('admin.spmb.dashboard.update'), [
            ...$this->validSpmbData(),
            'introduction' => 'Informasi terbaru setelah disimpan ulang.',
            'contact_whatsapp' => '',
        ])->assertRedirect();
        $this->get(route('spmb.public'))->assertOk()
            ->assertSee('Informasi terbaru setelah disimpan ulang.')
            ->assertDontSee('https://wa.me/6281234567890')
            ->assertDontSee('Hubungi panitia melalui WhatsApp');
    }

    public function test_public_spmb_stacks_poster_announcement_and_contact_in_separate_cards(): void
    {
        SpmbSetting::create([
            'title' => 'Informasi SPMB',
            'school_year' => '2027/2028',
            'introduction' => "Pendaftaran sudah dibuka.\n\n• Jalur prestasi\n• Jalur reguler",
            'contact_name' => 'Panitia Penerimaan',
            'contact_role' => 'Hubungi pada jam kerja',
            'contact_whatsapp' => '6281234567890',
            'published' => true,
        ]);

        $response = $this->get(route('spmb.public'))->assertOk()
            ->assertSee('📢 Informasi SPMB')
            ->assertSee('Tahun ajaran 2027/2028')
            ->assertSee('Jalur prestasi')
            ->assertSee('Panitia Penerimaan')
            ->assertSee('+6281234567890')
            ->assertSee('Hubungi Panitia');
        $html = $response->getContent();
        $posterPosition = strpos($html, 'spmb-visitor-poster-card');
        $announcementPosition = strpos($html, 'spmb-visitor-announcement');
        $contactPosition = strpos($html, 'spmb-visitor-contact');

        $this->assertNotFalse($posterPosition);
        $this->assertNotFalse($announcementPosition);
        $this->assertNotFalse($contactPosition);
        $this->assertTrue($posterPosition < $announcementPosition && $announcementPosition < $contactPosition);
        $this->assertSame(3, substr_count($html, 'class="spmb-visitor-card spmb-visitor-'));
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
            ...$this->validSpmbData(),
            'poster' => UploadedFile::fake()->create('bukan-gambar.txt', 10, 'text/plain'),
            'introduction' => 'Keterangan baru',
            'contact_whatsapp' => 'nomor-salah',
        ])->assertSessionHasErrors(['poster', 'contact_whatsapp']);

        $settings = SpmbSetting::query()->findOrFail(1);
        $this->assertSame($path, $settings->poster_path);
        $this->assertSame('Keterangan tersimpan', $settings->introduction);
        $this->assertSame('6281234567890', $settings->contact_whatsapp);
        Storage::disk('public')->assertExists($path);
    }

    public function test_oversized_total_request_shows_a_clear_error_and_does_not_clear_existing_spmb_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        SpmbSetting::create([
            'introduction' => 'Pengumuman tersimpan',
            'contact_whatsapp' => '6281234567890',
            'published' => true,
        ]);

        $this->actingAs($admin)
            ->withServerVariables(['CONTENT_LENGTH' => (string) (17 * 1024 * 1024)])
            ->put(route('admin.spmb.dashboard.update'), [
                'introduction' => 'Pengumuman baru',
                'contact_whatsapp' => '081234567890',
            ])
            ->assertStatus(413)
            ->assertSee('Ukuran data melebihi batas server')
            ->assertSee('Perubahan belum disimpan');

        $settings = SpmbSetting::query()->findOrFail(1);
        $this->assertSame('Pengumuman tersimpan', $settings->introduction);
        $this->assertSame('6281234567890', $settings->contact_whatsapp);
    }

    public function test_oversized_poster_has_a_clear_validation_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);

        $this->actingAs($admin)->put(route('admin.spmb.dashboard.update'), [
            'poster' => UploadedFile::fake()->image('poster-besar.jpg')->size(11 * 1024),
            'introduction' => 'Pengumuman tetap belum disimpan.',
            'contact_whatsapp' => '081234567890',
        ])->assertSessionHasErrors(['poster' => 'Ukuran poster maksimal 10 MB. Kompres gambar atau pilih file yang lebih kecil, lalu simpan kembali.']);

        $this->assertDatabaseMissing('spmb_settings', ['id' => 1]);
    }

    public function test_php_upload_failure_has_an_actionable_message_and_preserves_saved_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        SpmbSetting::create([
            'introduction' => 'Pengumuman tersimpan',
            'contact_whatsapp' => '6281234567890',
            'published' => true,
        ]);
        $temporaryFile = UploadedFile::fake()->create('poster.jpg')->getPathname();
        $failedUpload = new UploadedFile($temporaryFile, 'poster.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

        $this->actingAs($admin)->put(route('admin.spmb.dashboard.update'), [
            'poster' => $failedUpload,
            'introduction' => 'Perubahan yang belum tersimpan',
            'contact_whatsapp' => '081234567890',
        ])->assertSessionHasErrors([
            'poster' => 'Poster gagal diunggah. Batas PHP saat ini lebih kecil dari file yang dipilih atau koneksi terputus. Gunakan poster maksimal 10 MB dan jalankan server lokal dengan perintah bash tools/serve.',
        ]);

        $settings = SpmbSetting::query()->findOrFail(1);
        $this->assertSame('Pengumuman tersimpan', $settings->introduction);
        $this->assertSame('6281234567890', $settings->contact_whatsapp);
    }

    public function test_legacy_spmb_fields_are_preserved_but_no_longer_shown_in_admin_or_public_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $linkId = (string) Str::uuid();
        SpmbSetting::create([
            'title' => 'Penerimaan Murid Baru',
            'important_links' => [[
                'id' => $linkId,
                'title' => 'Formulir SPMB',
                'url' => 'https://example.test/form',
                'description' => 'Formulir pendaftaran daring.',
                'category' => 'registration',
                'published' => true,
                'sort_order' => 3,
            ]],
        ]);

        $this->actingAs($admin)->get(route('admin.spmb.dashboard'))
            ->assertOk()
            ->assertSee('Poster SPMB')
            ->assertDontSee('Formulir SPMB');

        $this->actingAs($admin)->put(route('admin.spmb.dashboard.update'), [
            'introduction' => 'Pengumuman baru',
        ])->assertRedirect()->assertSessionHas('success');

        $link = SpmbSetting::query()->findOrFail(1)->important_links[0];
        $this->assertSame($linkId, $link['id']);
        $this->assertSame('registration', $link['category']);
        $this->assertTrue($link['published']);
        $this->get(route('spmb.public'))->assertOk()->assertDontSee('Formulir SPMB')->assertSee('Pengumuman baru');
    }

    private function validSpmbData(array $overrides = []): array
    {
        return array_replace_recursive([
            'title' => 'Penerimaan Murid Baru',
            'school_year' => '',
            'introduction' => '',
            'poster_caption' => '',
            'schedule' => [],
            'requirements' => [],
            'steps' => [],
            'documents' => [],
            'quota' => '',
            'fee_information' => '',
            'registration_url' => '',
            'important_links' => [],
            'contact_name' => '',
            'contact_role' => '',
            'contact_whatsapp' => '',
            'closing_text' => '',
            'extra_sections' => [],
            'design' => [
                'font_family' => 'jakarta', 'font_size' => '16', 'text_color' => '#43564a',
                'background_color' => '#f6faf7', 'card_color' => '#ffffff', 'accent_color' => '#166534',
                'card_radius' => '16', 'poster_width' => '680', 'poster_fit' => 'contain',
            ],
        ], $overrides);
    }
}
