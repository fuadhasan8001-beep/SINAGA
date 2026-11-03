<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Tests\TestCase;

class FooterSettingsWorkflowTest extends TestCase
{
    public function test_footer_settings_are_admin_only_and_render_from_saved_database_values(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $this->actingAs($teacher)->get(route('admin.footer.edit'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin)->get(route('admin.footer.edit'))
            ->assertOk()
            ->assertSee('Pengaturan Footer')
            ->assertSee('Nama sekolah, slogan, dan logo dikelola di halaman Kelola Profil.')
            ->assertDontSee('name="school_name"', false)
            ->assertDontSee('school_logo_path')
            ->assertSee('data-footer-add="navigation"', false)
            ->assertSee('data-footer-add="social"', false);

        $this->put(route('admin.settings.update'), [
            'section' => 'profile',
            'settings' => [
                'school_name' => 'SMPN 1 Gondang Utama',
                'school_tagline' => 'Belajar dan bertumbuh',
            ],
        ])->assertRedirect(route('admin.dashboard', ['section' => 'profil']));

        $this->put(route('admin.footer.update'), [
            'footer_description' => 'Sekolah yang ramah dan berprestasi.',
            'address' => 'Jl. Pendidikan No. 1',
            'email' => 'sekolah@example.sch.id',
            'phone' => '+62 321 123456',
            'footer_navigation' => [
                ['label' => 'Beranda', 'url' => '/#beranda'],
                ['label' => 'Profil', 'url' => '/#profil'],
            ],
            'footer_social' => [
                ['label' => 'Instagram', 'url' => 'https://instagram.com/smpn1gondang'],
                ['label' => '', 'url' => ''],
            ],
            'footer_copyright_year' => 2026,
            'footer_copyright_name' => 'SMPN 1 Gondang',
            'footer_copyright_note' => 'Hak cipta dilindungi.',
        ])->assertRedirect(route('admin.footer.edit'));

        $this->assertSame('SMPN 1 Gondang Utama', json_decode(SiteSetting::whereKey('school_name')->value('value'), true));
        $this->assertSame('Belajar dan bertumbuh', json_decode(SiteSetting::whereKey('school_tagline')->value('value'), true));
        $this->assertSame([
            ['label' => 'Beranda', 'url' => '/#beranda'],
            ['label' => 'Profil', 'url' => '/#profil'],
        ], json_decode(SiteSetting::whereKey('footer_navigation')->value('value'), true));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('SMPN 1 Gondang Utama')
            ->assertSee('Sekolah yang ramah dan berprestasi.')
            ->assertSee('sekolah@example.sch.id')
            ->assertSee('instagram.com/smpn1gondang')
            ->assertSee('Hak cipta dilindungi.');
    }
}
