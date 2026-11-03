<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    protected $fillable = ['author_id','author_name','updated_by','updated_by_name','category_id','title','slug','excerpt','body','cover_image','status','revision_note','published_at'];
    protected function casts(): array { return ['published_at' => 'datetime']; }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function lastEditor(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function getAuthorDisplayNameAttribute(): string { return $this->author?->name ?? $this->author_name ?? 'Pengguna dihapus'; }
    public function getLastEditorDisplayNameAttribute(): ?string { return $this->lastEditor?->name ?? $this->updated_by_name; }
    public function scopePublished($query) { return $query->where('status','published')->whereNotNull('published_at')->where('published_at','<=',now()); }
    public function editableBy(User $user): bool { return $user->isAdmin() || ($this->author_id===$user->id && in_array($this->status,['draft','revision','rejected'],true)); }
}
