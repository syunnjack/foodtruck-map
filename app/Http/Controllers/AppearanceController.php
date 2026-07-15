<?php

namespace App\Http\Controllers;

use App\Models\AppearanceSlot;
use Illuminate\Http\Request;

class AppearanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AppearanceSlot::with('truck')
            ->where('appearance_date', '>=', now()->toDateString())
            ->orderBy('appearance_date')
            ->orderBy('start_time');

        if ($request->filled('area')) {
            $query->where('area', $request->input('area'));
        }

        $slots = $query->get();
        $areas = AppearanceSlot::query()
            ->where('appearance_date', '>=', now()->toDateString())
            ->distinct()
            ->pluck('area');

        return view('appearances.index', compact('slots', 'areas'));
    }
}
