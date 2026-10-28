<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentEntry extends Model
{
    protected $fillable = ['author_id','type','title','slug','excerpt','body','image_path','document_path','location','starts_at','metadata','published','sort_order'];
    protected function casts(): array { return ['metadata'=>'array','published'=>'boolean','starts_at'=>'datetime']; }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function scopeVisible($query) { return $query->where('published',true); }
}
