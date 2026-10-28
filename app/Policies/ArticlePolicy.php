<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function view(User $user, Article $article): bool { return $user->isAdmin() || $article->author_id===$user->id; }
    public function update(User $user, Article $article): bool { return $user->isAdmin() || ($article->author_id===$user->id && in_array($article->status,['draft','revision','rejected'],true)); }
    public function delete(User $user, Article $article): bool { return $this->update($user,$article); }
    public function review(User $user, Article $article): bool { return $user->isAdmin() && $article->status==='pending'; }
}
