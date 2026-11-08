<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function view(User $user, Article $article): bool
    {
        return $user->isAdmin() || ($user->active && $user->role === 'teacher' && $article->author_id === $user->id);
    }

    public function update(User $user, Article $article): bool
    {
        return $this->view($user, $article);
    }

    public function delete(User $user, Article $article): bool
    {
        return $this->view($user, $article);
    }
    public function review(User $user, Article $article): bool { return $user->isAdmin() && $article->status==='pending'; }
}
