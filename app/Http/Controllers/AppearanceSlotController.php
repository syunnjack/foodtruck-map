<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Support\ContentModeration;
use Illuminate\Http\Request;

class AppearanceSlotController extends Controller
{
    public function store(Request $request, Truck $truck)
    {
        if (! empty($request->input('website'))) {
            return back()->with('success', '投稿を受け付けました。');
        }

        $validated = $request->validate([
            'area' => 'required|string|max:255',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'appearance_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'comment' => 'nullable|string|max:1000',
            'nickname' => 'nullable|string|max:30',
        ]);

        if (! empty($validated['comment']) && ContentModeration::containsNgWord($validated['comment'])) {
            return back()->withErrors(['comment' => '投稿内容に使用できない文字列が含まれています。'])->withInput();
        }

        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("appearance-slot:{$truck->id}:{$ipHash}", 30)) {
            return back()->withErrors(['appearance_date' => '投稿間隔が短すぎます。しばらく待ってから再度お試しください。'])->withInput();
        }

        $truck->appearanceSlots()->create([
            'area' => $validated['area'],
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'appearance_date' => $validated['appearance_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'comment' => $validated['comment'] ?? null,
            'nickname' => ($validated['nickname'] ?? '') !== '' ? $validated['nickname'] : '匿名',
            'ip_hash' => $ipHash,
        ]);

        return redirect()->route('trucks.show', $truck)->with('success', '出店情報を投稿しました。ありがとうございます。');
    }
}
