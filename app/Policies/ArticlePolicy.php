<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function view(User $user, Article $article): bool { return $user->active && in_array($user->role, ['admin', 'teacher'], true); }
    public function update(User $user, Article $article): bool { return $user->active && in_array($user->role, ['admin', 'teacher'], true); }
    public function delete(User $user, Article $article): bool { return $user->active && in_array($user->role, ['admin', 'teacher'], true); }
    public function review(User $user, Article $article): bool { return $user->isAdmin() && $article->status==='pending'; }
}
