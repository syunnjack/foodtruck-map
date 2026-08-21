# foodtruck-map（foodtruck-map.jp）

キッチンカーが出店する場所を、住所と出典つきでまとめた地図。

- 本番URL: https://foodtruck-map.jp
- 配信: Xserver（`/home/xs501620/foodtruck-map.jp/`）
- Laravel。`public_html` は公開ディレクトリ、`app/` にアプリ本体

## デプロイの癖（先に読むこと）

**通常のデプロイ（`deploy.yml`）は `public/` と `resources/` しか送りません。**
`app/` `routes/` `database/` はサーバーに届きません。

このため、ビューから新しいルートやモデルを参照すると、本番だけ500になります
（実際にトップとトラック一覧を落としたことがあります）。ビューが新しい
ルートに触れるときは `Route::has()` で存在を確認してから使ってください。

`app/` `routes/` `database/` を送って migrate と seed まで流すのは
**`import-data.yml`（手動実行）** です。出店場所のデータを更新したら、
これを実行しないと本番に反映されません。

## 掲載しているデータ

自治体・公園管理者が公式に公表している出店場所だけを載せています。
推測で埋めることはしません。日々の出店予定は自治体が毎月更新するため
持たず、出典先の公式ページへ案内します。

| 出典 | 件数の目安 |
|---|---|
| 公益財団法人 東京都公園協会（都立公園の募集要項） | 46 |
| 世田谷区・杉並区・江戸川区・町田市・さいたま市 | 26 |
| 春日市（福岡）・君津市（千葉）・埼玉県公園緑地協会 | 5 |

### データの作り直し

```
python scripts/build-spots.py
```

- `scripts/fetch-tokyo-parks.py` が都立公園の一覧を募集要項のPDFから取ります
- `scripts/data/municipal-spots.json` は、自治体ごとにページの作りが違って
  機械で取れないため、確認した内容を手で置いてあります
- 座標は国土地理院の住所検索APIで取ります。すでに座標のある行は引き直しません
- 出力先は `database/data/spots.json`

`scripts/lib/pdf_text.py` は、poppler も pypdf も入らない環境で PDF の本文を
読むために自前で書いたものです。ToUnicode を持たないPDF（名古屋市の仕様書など）は
読めません。

### 全国展開が進まない理由

**出店場所の一覧をまとめて公表している自治体は、ほとんどありません。**
多くは「今月の出店予定」を毎月PDFで出すだけか、そもそも公表していません。
横浜市と和泉市は実施していましたが、いずれも試行が終わりページごと消えています。
東京都公園協会の募集要項は例外的に、住所つきの一覧が載っています。

## 掲載訂正・削除

`info@foodtruck-map.jp`。画面にも表示しています。

## Commands

- `php artisan serve`: 開発サーバー
- `php artisan migrate`: マイグレーション
- `php artisan db:seed --class=SpotSeeder`: 出店場所の取り込み
