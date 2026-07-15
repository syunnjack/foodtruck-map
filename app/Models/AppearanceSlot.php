<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppearanceSlot extends Model
{
    protected $fillable = [
        'truck_id',
        'area',
        'lat',
        'lng',
        'appearance_date',
        'start_time',
        'end_time',
        'comment',
        'nickname',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'appearance_date' => 'date',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }
}
