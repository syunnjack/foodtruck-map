<?php

namespace App\Http\Controllers;

use App\Models\AppearanceSlot;
use App\Models\Favorite;
use App\Models\Truck;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request, Truck $truck)
    {
        $lineUserLocalId = $request->session()->get('line_user_local_id');

        if (! $lineUserLocalId) {
            return redirect()->route('line.login', ['truck' => $truck->id]);
        }

        $favorite = Favorite::where('line_user_id', $lineUserLocalId)
            ->where('truck_id', $truck->id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return back()->with('success', '通知登録を解除しました。');
        }

        Favorite::create([
            'line_user_id' => $lineUserLocalId,
            'truck_id' => $truck->id,
            'last_checked_slot_id' => AppearanceSlot::where('truck_id', $truck->id)->max('id') ?? 0,
        ]);

        return back()->with('success', '新しい出店情報が投稿されるとLINEでお知らせします。');
    }
}
