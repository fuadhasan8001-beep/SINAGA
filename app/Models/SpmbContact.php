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

    public function getWhatsappUrlAttribute(): ?string
    {
        $number=preg_replace('/\D+/','',$this->phone);
        if(str_starts_with($number,'00'))$number=substr($number,2);
        if(str_starts_with($number,'0'))$number='62'.substr($number,1);
        elseif(str_starts_with($number,'8'))$number='62'.$number;

        return strlen($number)>=9&&strlen($number)<=15?'https://wa.me/'.$number:null;
    }
}
