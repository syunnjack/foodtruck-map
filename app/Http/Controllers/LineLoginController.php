<?php

namespace App\Http\Controllers;

use App\Models\AppearanceSlot;
use App\Models\Favorite;
use App\Models\LineUser;
use App\Models\Truck;
use App\Support\LineMessaging;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LineLoginController extends Controller
{
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('line_login_state', $state);

        if ($request->filled('truck')) {
            $request->session()->put('line_login_intended_truck', (int) $request->input('truck'));
        }

        return redirect()->away(LineMessaging::authorizeUrl($state));
    }

    public function callback(Request $request)
    {
        $state = $request->query('state');
        $expectedState = $request->session()->pull('line_login_state');

        if (! $state || $state !== $expectedState) {
            return redirect()->route('appearances.index')->withErrors(['line' => 'LINEログインの検証に失敗しました。もう一度お試しください。']);
        }

        if (! $request->filled('code')) {
            return redirect()->route('appearances.index')->withErrors(['line' => 'LINEログインがキャンセルされました。']);
        }

        $token = LineMessaging::exchangeToken($request->input('code'));
        $claims = LineMessaging::verifyIdToken($token['id_token']);

        $lineUser = LineUser::updateOrCreate(
            ['line_user_id' => $claims['sub']],
            ['display_name' => $claims['name'] ?? null]
        );

        $request->session()->put('line_user_local_id', $lineUser->id);

        $intendedTruckId = $request->session()->pull('line_login_intended_truck');
        if ($intendedTruckId) {
            $truck = Truck::find($intendedTruckId);
            if ($truck) {
                Favorite::firstOrCreate(
                    ['line_user_id' => $lineUser->id, 'truck_id' => $truck->id],
                    ['last_checked_slot_id' => AppearanceSlot::where('truck_id', $truck->id)->max('id') ?? 0]
                );

                return redirect()->route('trucks.show', $truck)->with('success', '通知登録が完了しました。新しい出店情報が投稿されるとLINEでお知らせします。');
            }
        }

        return redirect()->route('appearances.index')->with('success', 'LINEログインが完了しました。');
    }
}
