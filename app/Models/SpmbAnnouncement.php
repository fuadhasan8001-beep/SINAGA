<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpmbAnnouncement extends Model
{
    protected $fillable = ['title','body','starts_at','published'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime','published' => 'boolean'];
    }
}