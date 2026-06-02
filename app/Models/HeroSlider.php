<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSlider extends Model
{
    protected $fillable = [
        'slider_type',
        'title',
        'subtitle',
        'description',
        'image_path',
        'cta_button_text',
        'cta_button_url',
        'sec_button_text',
        'sec_button_url',
        'is_active',
        'sort_order',
    ];
}
