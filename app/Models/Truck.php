<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    protected $fillable = [
        'name',
        'description',
        'cuisine_type',
        'area',
        'sns_url',
        'phone',
        'likes_count',
    ];

    public function appearanceSlots()
    {
        return $this->hasMany(AppearanceSlot::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
}
