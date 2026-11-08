<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Tests\TestCase;

class ArticleReturnNavigationTest extends TestCase
{
    public function test_article_return_link_uses_the_public_list_or_the_correct_authenticated_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $article = Article::create([
            'author_id' => $teacher->id,
            'author_name' => $teacher->name,
            'title' => 'Informasi Uji Navigasi',
            'slug' => 'informasi-uji-navigasi',
            'body' => '<p>Isi artikel uji.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get(route('information.article', $article->slug))
            ->assertOk()
            ->assertSee('href="'.route('information.articles').'"', false);

        $articleListPage = route('information.articles', ['page' => 2]);
        $this->withHeader('referer', $articleListPage)
            ->get(route('information.article', $article->slug))
            ->assertOk()
            ->assertSee('href="'.$articleListPage.'"', false);

        $this->actingAs($teacher)->get(route('article.show', $article->slug))
            ->assertOk()
            ->assertSee('href="'.route('guru.articles.index').'"', false)
            ->assertDontSee('href="'.route('admin.articles.index').'"', false);

        $this->actingAs($admin)->get(route('admin.website.article', $article->slug))
            ->assertOk()
            ->assertSee('href="'.route('admin.articles.index').'"', false);
    }
}
