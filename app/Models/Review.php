<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'truck_id',
        'nickname',
        'rating',
        'comment',
        'photo_path',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }
}
