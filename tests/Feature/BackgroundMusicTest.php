<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class BackgroundMusicTest extends TestCase
{
    public function test_background_music_is_only_rendered_once_on_public_pages_without_controls(): void
    {
        $path = public_path('audio/background-music.mp3');
        $this->assertFileExists($path);
        $this->assertGreaterThan(1024, filesize($path));

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'id="siteBackgroundMusic"'));
        $this->assertStringContainsString('audio/background-music.mp3', $html);
        $this->assertStringContainsString('loop preload="metadata"', $html);
        $this->assertStringNotContainsString('autoplay', $html);
        $this->assertDoesNotMatchRegularExpression('/<audio\\b[^>]*\\bcontrols(?:=|\\s|>)/i', $html);

        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('siteBackgroundMusic');

        auth()->logout();
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('siteBackgroundMusic');
    }

    public function test_background_music_is_available_on_each_public_navigation_destination(): void
    {
        foreach (['profile', 'academics', 'announcements', 'extracurriculars', 'gallery', 'contact', 'spmb.public'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('id="siteBackgroundMusic"', false);
        }
    }

    public function test_public_navigation_uses_public_routes_not_admin_management_routes(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['profile', 'academics', 'announcements', 'extracurriculars', 'gallery', 'contact'] as $route) {
            $this->assertStringContainsString('href="'.route($route).'"', $html);
        }

        $this->assertStringNotContainsString('href="'.route('admin.content.index', 'announcement').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.content.index', 'gallery').'"', $html);
    }

    public function test_homepage_hero_and_navigation_use_the_same_public_destinations(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['home', 'profile', 'academics', 'announcements', 'extracurriculars', 'gallery', 'contact', 'information.articles', 'spmb.public'] as $route) {
            $this->assertStringContainsString('href="'.route($route).'"', $html);
        }

        $this->assertStringContainsString('href="'.route('profile').'">Profil Sekolah</a>', $html);
        $this->assertStringContainsString('href="'.route('gallery').'">Jelajahi Galeri</a>', $html);

        foreach (['profile', 'academics', 'announcements', 'extracurriculars', 'gallery', 'contact'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('data-scroll-target=', false);
        }
    }

    public function test_admin_dashboard_hero_actions_open_the_matching_management_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.content.index', 'announcement').'"', false)
            ->assertSee('href="'.route('admin.content.index', 'gallery').'"', false)
            ->assertSee('site-admin-toggle--header', false)
            ->assertDontSee('id="siteBackgroundMusic"', false);
    }
}
