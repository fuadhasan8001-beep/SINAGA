<?php

namespace Tests\Unit;

use App\Services\ArticleHtml;
use PHPUnit\Framework\TestCase;

class ArticleHtmlTest extends TestCase
{
    public function test_article_content_is_sanitized_and_local_inline_images_are_preserved(): void
    {
        $clean=(new ArticleHtml())->clean('<h2>Berita</h2><figure><img src="/storage/media/123e4567-e89b-12d3-a456-426614174000.png" alt="Foto"><figcaption>Kegiatan sekolah</figcaption></figure><img src="https://tracker.test/pixel.png" onerror="alert(1)"><script>alert(2)</script><a href="javascript:alert(3)">tautan</a>');
        $this->assertStringContainsString('<h2>Berita</h2>',$clean);
        $this->assertStringContainsString('<figure>',$clean);
        $this->assertStringContainsString('Kegiatan sekolah',$clean);
        $this->assertStringContainsString('/storage/media/123e4567-e89b-12d3-a456-426614174000.png',$clean);
        $this->assertStringNotContainsString('tracker.test',$clean);
        $this->assertStringNotContainsString('onerror',$clean);
        $this->assertStringNotContainsString('<script>',$clean);
        $this->assertStringNotContainsString('javascript:',$clean);
    }
}
