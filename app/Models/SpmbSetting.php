<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpmbSetting extends Model
{
    protected $fillable = ['title','school_year','introduction','status','schedule','requirements','steps','documents','quota','fee_information','registration_url','poster_path','poster_caption','important_links','published','contact_name','contact_role','contact_whatsapp','closing_text','extra_sections','design_settings'];

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
            'requirements' => 'array',
            'steps' => 'array',
            'documents' => 'array',
            'important_links' => 'array',
            'extra_sections' => 'array',
            'design_settings' => 'array',
            'quota' => 'integer',
            'published' => 'boolean',
        ];
    }
}
