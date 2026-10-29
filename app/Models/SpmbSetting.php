<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpmbSetting extends Model
{
    protected $fillable = ['title','school_year','introduction','status','schedule','requirements','steps','documents','quota','fee_information','registration_url','poster_path','published'];

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
            'requirements' => 'array',
            'steps' => 'array',
            'documents' => 'array',
            'quota' => 'integer',
            'published' => 'boolean',
        ];
    }
}
