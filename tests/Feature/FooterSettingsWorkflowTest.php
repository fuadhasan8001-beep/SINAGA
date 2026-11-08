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
            ->assertSee('name="school_name"', false)
            ->assertSee('name="address_city"', false)
            ->assertSee('name="phone_alternative"', false)
            ->assertSee('data-footer-add="navigation"', false)
            ->assertSee('data-footer-add="social"', false);

        $this->put(route('admin.footer.update'), [
            'school_name' => 'SMPN 1 Gondang Utama',
            'school_tagline' => 'Belajar dan bertumbuh',
            'footer_description' => 'Sekolah yang ramah dan berprestasi.',
            'address' => 'Jl. Pendidikan No. 1',
            'address_city' => 'Tulungagung',
            'address_region' => 'Jawa Timur',
            'address_postal_code' => '66263',
            'address_map_url' => 'https://maps.google.com/?q=SMPN+1+Gondang',
            'email' => 'sekolah@example.sch.id',
            'phone' => '+62 321 123456',
            'phone_alternative' => '(0355) 123456',
            'footer_navigation' => [
                ['label' => 'Beranda', 'url' => '/#beranda'],
                ['label' => 'Profil', 'url' => '/#profil'],
            ],
            'footer_social' => [
                ['label' => 'Instagram', 'url' => 'https://instagram.com/smpn1gondang'],
                ['label' => 'Facebook', 'url' => 'https://facebook.com/smpn1gondang'],
                ['label' => 'YouTube', 'url' => 'https://youtube.com/@smpn1gondang'],
                ['label' => 'TikTok', 'url' => 'https://tiktok.com/@smpn1gondang'],
                ['label' => 'Mastodon', 'url' => 'https://mastodon.social/@smpn1gondang'],
            ],
            'footer_copyright_year' => 2026,
            'footer_copyright_name' => 'SMPN 1 Gondang',
            'footer_copyright_note' => 'Hak cipta dilindungi.',
        ])->assertRedirect(route('admin.footer.edit'));

        $this->assertSame('SMPN 1 Gondang Utama', json_decode(SiteSetting::whereKey('school_name')->value('value'), true));
        $this->assertSame('Belajar dan bertumbuh', json_decode(SiteSetting::whereKey('school_tagline')->value('value'), true));
        $this->assertSame('Jawa Timur', json_decode(SiteSetting::whereKey('address_region')->value('value'), true));
        $this->assertSame('(0355) 123456', json_decode(SiteSetting::whereKey('phone_alternative')->value('value'), true));
        $this->assertTrue(json_decode(SiteSetting::whereKey('footer_social_configured')->value('value'), true));
        $this->assertSame([
            ['label' => 'Beranda', 'url' => '/#beranda'],
            ['label' => 'Profil', 'url' => '/#profil'],
        ], json_decode(SiteSetting::whereKey('footer_navigation')->value('value'), true));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('SMPN 1 Gondang Utama')
            ->assertSee('Sekolah yang ramah dan berprestasi.')
            ->assertSee('Jl. Pendidikan No. 1, Tulungagung, Jawa Timur, 66263')
            ->assertSee('footer-contact-item--location', false)
            ->assertSee('href="https://maps.google.com/?q=SMPN+1+Gondang"', false)
            ->assertSee('sekolah@example.sch.id')
            ->assertSee('instagram.com/smpn1gondang')
            ->assertSee('facebook.com/smpn1gondang')
            ->assertSee('youtube.com/@smpn1gondang')
            ->assertSee('tiktok.com/@smpn1gondang')
            ->assertSee('mastodon.social/@smpn1gondang')
            ->assertSee('footer-social-copy"><strong>Instagram</strong><span>@smpn1gondang</span>', false)
            ->assertSee('footer-social-copy"><strong>YouTube</strong><span>@smpn1gondang</span>', false)
            ->assertSee('footer-social-copy"><strong>Mastodon</strong><span>@smpn1gondang</span>', false)
            ->assertDontSee('>instagram.com</span>', false)
            ->assertDontSee('>youtube.com</span>', false)
            ->assertSee('wa.me/62321123456', false)
            ->assertSee('tel:0355123456', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('Hak cipta dilindungi.')
            ->assertSee('class="footer-watermark"', false)
            ->assertSee('by ESEMKITA 2026');

        $this->put(route('admin.footer.update'), [
            'school_name' => 'SMPN 1 Gondang Utama', 'school_tagline' => 'Belajar dan bertumbuh',
            'footer_description' => 'Sekolah yang ramah dan berprestasi.', 'address' => 'Jl. Pendidikan No. 1',
            'address_city' => 'Tulungagung', 'address_region' => 'Jawa Timur', 'address_postal_code' => '66263',
            'email' => 'sekolah@example.sch.id', 'phone' => '+62 321 123456', 'phone_alternative' => '(0355) 123456',
            'footer_navigation' => [['label' => 'Beranda', 'url' => '/#beranda']], 'footer_social' => [],
            'footer_copyright_year' => 2026, 'footer_copyright_name' => 'SMPN 1 Gondang', 'footer_copyright_note' => '',
        ])->assertRedirect(route('admin.footer.edit'));
        $this->get(route('home'))->assertOk()->assertDontSee('footer-social-card')->assertDontSee('Media Sosial');
    }

    public function test_footer_hides_null_and_empty_fields_and_their_group_headings(): void
    {
        foreach ([
            'school_name', 'school_tagline', 'school_logo_path', 'footer_description',
            'address', 'address_city', 'address_region', 'address_postal_code', 'address_map_url',
            'phone', 'phone_alternative', 'email', 'footer_copyright_year',
            'footer_copyright_name', 'footer_copyright_note',
        ] as $key) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => json_encode(null)]);
        }
        SiteSetting::updateOrCreate(['key' => 'footer_navigation'], ['value' => json_encode([])]);
        SiteSetting::updateOrCreate(['key' => 'footer_social'], ['value' => json_encode(null)]);
        SiteSetting::updateOrCreate(['key' => 'footer_social_configured'], ['value' => json_encode(true)]);

        $response = $this->get(route('home'))->assertOk();
        preg_match('/<footer class="footer">.*?<\/footer>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $footer = $matches[0];

        foreach ([
            'footer-identity', 'footer-navigation', 'footer-contact', 'footer-social-column',
            'footer-brand-name', 'footer-links', 'footer-social-card', 'Hubungi Kami',
            'Media Sosial', 'Jelajahi', '©', 'null',
        ] as $emptyContent) {
            $this->assertStringNotContainsString($emptyContent, $footer);
        }
        $this->assertStringContainsString('by ESEMKITA 2026', $footer);
    }

    public function test_footer_discards_invalid_social_urls_and_whitespace_values(): void
    {
        SiteSetting::updateOrCreate(['key' => 'school_name'], ['value' => json_encode('   ')]);
        SiteSetting::updateOrCreate(['key' => 'address'], ['value' => json_encode('null')]);
        SiteSetting::updateOrCreate(['key' => 'footer_social'], ['value' => json_encode([
            ['label' => 'Instagram', 'url' => 'javascript:alert(1)'],
            ['label' => 'YouTube', 'url' => ''],
        ])]);

        $response = $this->get(route('home'))->assertOk()->assertDontSee('footer-social-card')->assertDontSee('Hubungi Kami');
        preg_match('/<footer class="footer">.*?<\/footer>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $this->assertStringNotContainsString('>null<', $matches[0]);
        $this->assertStringNotContainsString('>undefined<', $matches[0]);
    }
}
