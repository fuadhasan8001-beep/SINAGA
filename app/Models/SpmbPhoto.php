<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpmbPhoto extends Model
{
    protected $fillable = ['title', 'caption', 'image_path', 'published', 'sort_order'];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }
}
