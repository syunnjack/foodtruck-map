<?php

namespace App\Console\Commands;

use App\Models\AppearanceSlot;
use App\Models\Favorite;
use App\Support\LineMessaging;
use Illuminate\Console\Command;

class CheckAppearanceWatches extends Command
{
    protected $signature = 'appearances:check-watches';

    protected $description = 'ウォッチ登録されたフードトラックに新しい出店情報が投稿されていないか確認し、LINEで通知する';

    public function handle(): int
    {
        $favorites = Favorite::with('lineUser')->get();

        foreach ($favorites as $favorite) {
            if (! $favorite->lineUser) {
                continue;
            }

            $since = $favorite->last_checked_slot_id ?? 0;
            $newSlots = AppearanceSlot::where('truck_id', $favorite->truck_id)
                ->where('id', '>', $since)
                ->get();

            if ($newSlots->isEmpty()) {
                continue;
            }

            $latest = $newSlots->sortByDesc('id')->first();
            $favorite->loadMissing('truck');
            LineMessaging::push(
                $favorite->lineUser->line_user_id,
                "「{$favorite->truck->name}」の新しい出店情報が投稿されました: "
                . $latest->appearance_date->format('n/j')
                . ' ' . $latest->area . ' '
                . substr($latest->start_time, 0, 5) . '〜' . substr($latest->end_time, 0, 5)
            );

            // last_checked_slot_idは検知カーソル。idは常に厳密単調増加のため、
            // created_at(秒精度)を使った場合に起こりうる同一秒内の複数投稿の取りこぼしが起きない。
            $favorite->update(['last_checked_slot_id' => $newSlots->max('id')]);
        }

        return self::SUCCESS;
    }
}
