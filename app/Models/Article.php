<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    protected $fillable = ['author_id','category_id','title','slug','excerpt','body','cover_image','status','revision_note','published_at'];
    protected function casts(): array { return ['published_at' => 'datetime']; }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function scopePublished($query) { return $query->where('status','published')->whereNotNull('published_at')->where('published_at','<=',now()); }
    public function editableBy(User $user): bool { return $user->isAdmin() || ($this->author_id===$user->id && in_array($this->status,['draft','revision','rejected'],true)); }
}
