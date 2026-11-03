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
    }
}
