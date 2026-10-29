<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpmbContact extends Model
{
    protected $fillable = ['name','role','phone','office_hours','note','published','sort_order'];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }
}