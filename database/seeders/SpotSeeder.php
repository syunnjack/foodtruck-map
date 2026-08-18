<?php

namespace Database\Seeders;

use App\Models\Spot;
use Illuminate\Database\Seeder;

/**
 * database/data/spots.json の内容を spots テーブルへ入れる。
 * 名前と住所が同じものは更新するだけなので、何度実行しても重複しない。
 */
class SpotSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/spots.json');

        if (! is_file($path)) {
            $this->command->warn("データファイルが見つかりません: {$path}");

            return;
        }

        $data = json_decode(file_get_contents($path), true);

        foreach ($data['spots'] ?? [] as $spot) {
            Spot::updateOrCreate(
                ['name' => $spot['name'], 'address' => $spot['address']],
                [
                    'area' => $spot['area'],
                    'lat' => $spot['lat'],
                    'lng' => $spot['lng'],
                    'hours' => $spot['hours'] ?? null,
                    'note' => $spot['note'] ?? null,
                    'source_url' => $spot['sourceUrl'],
                    'source_label' => $spot['sourceLabel'],
                ]
            );
        }

        $this->command->info('出店スポットを ' . count($data['spots'] ?? []) . ' 件登録しました。');
    }
}
