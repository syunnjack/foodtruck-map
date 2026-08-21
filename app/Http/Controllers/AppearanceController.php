<?php

namespace App\Http\Controllers;

use App\Models\AppearanceSlot;
use App\Models\Spot;
use Illuminate\Http\Request;

class AppearanceController extends Controller
{
    public function index(Request $request)
    {
        $area = $request->input('area');

        $query = AppearanceSlot::with('truck')
            ->where('appearance_date', '>=', now()->toDateString())
            ->orderBy('appearance_date')
            ->orderBy('start_time');

        if ($request->filled('area')) {
            $query->where('area', $area);
        }

        $slots = $query->get();

        // 利用者の投稿はまだ少ないため、自治体が公表している出店場所も
        // トップページの地図と一覧に出す。こちらは常に中身がある。
        $spotQuery = Spot::query()->orderBy('area')->orderBy('name');

        if ($request->filled('area')) {
            $spotQuery->where('area', 'like', $area . '%');
        }

        $spots = $spotQuery->get();

        // 絞り込みの選択肢は、投稿と出店場所の両方から作る。
        $areas = AppearanceSlot::query()
            ->where('appearance_date', '>=', now()->toDateString())
            ->distinct()
            ->pluck('area')
            ->merge(Spot::query()->distinct()->pluck('area'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('appearances.index', compact('slots', 'spots', 'areas'));
    }
}
