<?php

namespace App\Http\Controllers;

use App\Models\Spot;

class SpotController extends Controller
{
    /**
     * 出店スポットの一覧。地域ごとにまとめて表示する。
     */
    public function index()
    {
        $spots = Spot::query()->orderBy('area')->orderBy('name')->get();
        $spotsByArea = $spots->groupBy('area');

        return view('spots.index', compact('spots', 'spotsByArea'));
    }

    public function show(Spot $spot)
    {
        $sameArea = Spot::query()
            ->where('area', $spot->area)
            ->where('id', '!=', $spot->id)
            ->orderBy('name')
            ->get();

        return view('spots.show', compact('spot', 'sameArea'));
    }
}
