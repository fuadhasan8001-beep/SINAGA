<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class BackgroundMusicTest extends TestCase
{
    public function test_background_music_is_only_rendered_once_on_public_pages_without_controls(): void
    {
        $directory = public_path('audio');
        $path = $directory.'/background-music.mp3';
        if (! is_dir($directory)) mkdir($directory, 0755, true);
        file_put_contents($path, 'test audio placeholder');

        try {
            $html = $this->get(route('home'))->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'id="siteBackgroundMusic"'));
            $this->assertStringContainsString('autoplay loop preload="metadata"', $html);
            $this->assertDoesNotMatchRegularExpression('/<audio\\b[^>]*\\bcontrols(?:=|\\s|>)/i', $html);

            $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
            $this->actingAs($admin)->get(route('admin.dashboard'))
                ->assertOk()
                ->assertDontSee('siteBackgroundMusic');
        } finally {
            @unlink($path);
        }
    }
}
