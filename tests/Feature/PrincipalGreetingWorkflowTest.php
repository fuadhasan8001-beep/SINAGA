<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrincipalGreetingWorkflowTest extends TestCase
{
    public function test_profile_shows_a_bounded_greeting_excerpt_and_a_detail_link(): void
    {
        $greeting = str_repeat('Sambutan kepala sekolah untuk seluruh peserta didik. ', 8).'KALIMAT-PENUTUP-RAHASIA';
        SiteSetting::create(['key' => 'principal_greeting', 'value' => json_encode($greeting)]);
        SiteSetting::create(['key' => 'principal', 'value' => json_encode('Kepala Sekolah Uji')]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sambutan Kepala Sekolah')
            ->assertSee('Lihat Selengkapnya')
            ->assertSee(route('principal-greeting'), false)
            ->assertSee('…')
            ->assertDontSee('KALIMAT-PENUTUP-RAHASIA');
    }

    public function test_detail_renders_escaped_paragraphs_banner_and_principal_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('school-photos/banner.jpg', 'banner');
        Storage::disk('public')->put('school-photos/principal.jpg', 'portrait');
        foreach ([
            'school_name' => 'SMPN 1 Gondang',
            'principal' => 'Kepala Sekolah Uji',
            'hero_photo_path' => 'school-photos/banner.jpg',
            'principal_photo_path' => 'school-photos/principal.jpg',
            'principal_greeting' => "Salam pembuka.\n\nIsi <script>alert('x')</script>.\n\nSalam penutup.",
        ] as $key => $value) {
            SiteSetting::create(['key' => $key, 'value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
        }

        $this->get(route('principal-greeting'))
            ->assertOk()
            ->assertSee('Sambutan Kepala Sekolah')
            ->assertSee('Kepala Sekolah Uji')
            ->assertSee('class="active"', false)
            ->assertSee('aria-current="location"', false)
            ->assertSee('<p>Salam pembuka.</p>', false)
            ->assertSee('Isi &lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;.', false)
            ->assertDontSee('<script>alert', false)
            ->assertSee(Storage::disk('public')->url('school-photos/banner.jpg'), false)
            ->assertSee(Storage::disk('public')->url('school-photos/principal.jpg'), false)
            ->assertSee(route('profile'), false);
    }

    public function test_detail_has_fallbacks_when_greeting_and_photos_are_missing(): void
    {
        Storage::fake('public');

        $this->get(route('principal-greeting'))
            ->assertOk()
            ->assertSee('Sambutan kepala sekolah sedang disiapkan.')
            ->assertSee('Foto kepala sekolah belum tersedia', false)
            ->assertSee('principal-greeting-banner-placeholder', false)
            ->assertDontSee('/storage/school-photos/');
    }

    public function test_admin_can_edit_greeting_and_preview_existing_photos(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('school-photos/banner.jpg', 'banner');
        Storage::disk('public')->put('school-photos/principal.jpg', 'portrait');
        SiteSetting::create(['key' => 'hero_photo_path', 'value' => json_encode('school-photos/banner.jpg')]);
        SiteSetting::create(['key' => 'principal_photo_path', 'value' => json_encode('school-photos/principal.jpg')]);
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);

        $this->actingAs($admin)->get(route('admin.settings.edit', ['section' => 'profile']))
            ->assertOk()
            ->assertSee('Kepala Sekolah &amp; Sambutan', false)
            ->assertSee('rows="10"', false)
            ->assertSee('Pisahkan paragraf dengan satu baris kosong.')
            ->assertSee(route('admin.photos.edit', 'principal'), false)
            ->assertSee(route('admin.settings.edit', ['section' => 'hero']), false)
            ->assertSee(Storage::disk('public')->url('school-photos/principal.jpg'), false)
            ->assertDontSee('Gambar Sampul Sambutan')
            ->assertDontSee(Storage::disk('public')->url('school-photos/banner.jpg'), false);

        $greeting = "Salam pembuka.\n\nIsi sambutan.\n\nSalam penutup.";
        $this->put(route('admin.settings.update'), ['section' => 'profile', 'settings' => [
            'principal' => 'Kepala Sekolah Uji',
            'principal_greeting' => $greeting,
        ]])->assertRedirect(route('admin.dashboard', ['section' => 'profil']))
            ->assertSessionHas('success');

        $this->assertSame(json_encode($greeting, JSON_UNESCAPED_UNICODE), SiteSetting::find('principal_greeting')->value);
        $this->get(route('home'))->assertOk()
            ->assertSee('Salam pembuka.')
            ->assertSee('Lihat Selengkapnya')
            ->assertSee(route('principal-greeting'), false);
        $this->get(route('principal-greeting'))->assertOk()->assertSee('<p>Salam penutup.</p>', false);
    }

    public function test_admin_greeting_preview_returns_to_the_admin_profile_without_rendering_music(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);

        $this->actingAs($admin)->get(route('admin.principal-greeting'))
            ->assertOk()
            ->assertSee(route('admin.dashboard').'#profil', false)
            ->assertDontSee('id="siteBackgroundMusic"', false);
    }
}
