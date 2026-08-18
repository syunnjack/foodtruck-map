<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * キッチンカーが出店する場所。自治体が公表している情報を編集部が登録する。
 * 利用者が投稿する Truck / AppearanceSlot とは別に扱う。
 */
class Spot extends Model
{
    protected $fillable = [
        'name',
        'address',
        'area',
        'lat',
        'lng',
        'hours',
        'note',
        'source_url',
        'source_label',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];
}
