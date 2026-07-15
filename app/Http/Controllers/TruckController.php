<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Support\ContentModeration;
use Illuminate\Http\Request;

class TruckController extends Controller
{
    public function index(Request $request)
    {
        $query = Truck::query();

        if ($request->filled('area')) {
            $query->where('area', $request->input('area'));
        }

        $trucks = $query->latest()->get();
        $areas = Truck::query()->whereNotNull('area')->distinct()->pluck('area');

        return view('trucks.index', compact('trucks', 'areas'));
    }

    public function create()
    {
        return view('trucks.create');
    }

    public function store(Request $request)
    {
        if (! empty($request->input('website'))) {
            return redirect()->route('trucks.thanks');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'cuisine_type' => 'nullable|string|max:20',
            'area' => 'nullable|string|max:255',
            'sns_url' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if (ContentModeration::containsNgWord($validated['name'] . ' ' . ($validated['description'] ?? ''))) {
            return back()->withErrors(['name' => '投稿内容に使用できない文字列が含まれています。'])->withInput();
        }

        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("truck-create:{$ipHash}", 30)) {
            return back()->withErrors(['name' => '投稿間隔が短すぎます。しばらく待ってから再度お試しください。'])->withInput();
        }

        Truck::create($validated);

        return redirect()->route('trucks.thanks');
    }

    public function show(Truck $truck)
    {
        $truck->load(['reviews' => fn ($q) => $q->latest()]);
        $truck->load(['appearanceSlots' => fn ($q) => $q->where('appearance_date', '>=', now()->toDateString())->orderBy('appearance_date')->orderBy('start_time')]);

        $isWatching = session('line_user_local_id')
            ? $truck->favorites()->where('line_user_id', session('line_user_local_id'))->exists()
            : false;

        return view('trucks.show', compact('truck', 'isWatching'));
    }

    public function like(Request $request, Truck $truck)
    {
        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("like:{$truck->id}:{$ipHash}", 60)) {
            return response()->json(['error' => 'いいね！は少し時間を空けてから再度お試しください。'], 429);
        }

        $truck->increment('likes_count');
        $truck->refresh();

        return response()->json(['likes_count' => $truck->likes_count]);
    }

    public function sitemap()
    {
        $trucks = Truck::select('id', 'updated_at')->get();
        $xml = view('sitemap', compact('trucks'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
