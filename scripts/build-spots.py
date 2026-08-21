"""キッチンカーの出店場所をまとめて、database/data/spots.json を作る。

集めるもの:
  1. 都立公園等（公益財団法人 東京都公園協会の募集要項から自動で取る）
  2. 自治体が公式ページで公表しているもの（scripts/data/municipal-spots.json）

座標は国土地理院の住所検索APIで取る。すでに座標のある行は、そのまま使う
（同じ住所を毎回問い合わせないため）。

出典が確認できないものは入れない。日々の出店予定は自治体が毎月更新するので
持たず、出典先へ案内する。

使い方: python scripts/build-spots.py
"""
import json
import re
import subprocess
import sys
import time
import urllib.parse
import urllib.request
from datetime import date
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
OUTPUT = ROOT / 'database/data/spots.json'
MUNICIPAL = ROOT / 'scripts/data/municipal-spots.json'
TOKYO_SCRIPT = ROOT / 'scripts/fetch-tokyo-parks.py'

GEOCODER = 'https://msearch.gsi.go.jp/address-search/AddressSearch'
UA = ('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
      '(KHTML, like Gecko) Chrome/131.0 Safari/537.36')


def load_json(path: Path):
    return json.loads(path.read_text(encoding='utf-8'))


def tokyo_parks() -> list[dict]:
    """都立公園の一覧を、取得スクリプトを動かして受け取る。"""
    result = subprocess.run(
        [sys.executable, str(TOKYO_SCRIPT)],
        capture_output=True, text=True, encoding='utf-8',
    )

    if result.returncode != 0:
        print('都立公園の取得に失敗しました:', result.stderr.strip(), file=sys.stderr)
        return []

    return json.loads(result.stdout)


def normalise_address(address: str) -> str:
    """国土地理院の検索に通りやすい形にする。"""
    text = address.replace('－', '-').replace('ー', '-').replace('−', '-')
    text = re.sub(r'\s+', '', text)
    return text


def geocode(address: str) -> tuple[float, float] | None:
    """住所から座標を引く。見つからなければ、末尾を削って粗くしながら再挑戦する。"""
    candidates = [normalise_address(address)]

    # 「1-2-3」→「1-2」→「1」と粗くしていく。
    trimmed = candidates[0]
    for _ in range(3):
        shorter = re.sub(r'-[^-]+$', '', trimmed)
        if shorter == trimmed:
            break
        candidates.append(shorter)
        trimmed = shorter

    for candidate in candidates:
        query = urllib.parse.urlencode({'q': candidate})
        request = urllib.request.Request(f'{GEOCODER}?{query}', headers={'User-Agent': UA})

        try:
            with urllib.request.urlopen(request, timeout=30) as response:
                found = json.loads(response.read().decode())
        except Exception as error:
            print(f'    引けませんでした（{candidate}）: {error}', file=sys.stderr)
            time.sleep(2)
            continue

        time.sleep(0.6)

        if found:
            lng, lat = found[0]['geometry']['coordinates']
            return float(lat), float(lng)

    return None


def key_of(spot: dict) -> tuple[str, str]:
    return (spot['name'].strip(), spot.get('area', '').strip())


def main() -> None:
    existing = {}
    if OUTPUT.exists():
        for spot in load_json(OUTPUT).get('spots', []):
            existing[key_of(spot)] = spot

    municipal = load_json(MUNICIPAL)['spots']
    parks = tokyo_parks()

    print(f'自治体の公表ぶん: {len(municipal)}件 / 都立公園: {len(parks)}件')

    merged: dict[tuple[str, str], dict] = {}

    # 自治体の公表を先に入れる。同じ場所が都立公園側にもある場合、
    # 区市の案内のほうが利用者にとって具体的なので、そちらを残す。
    for spot in municipal + parks:
        key = key_of(spot)
        if key in merged:
            continue
        merged[key] = dict(spot)

    geocoded = 0
    for key, spot in merged.items():
        previous = existing.get(key)

        if previous and previous.get('lat') and previous.get('lng'):
            spot['lat'] = previous['lat']
            spot['lng'] = previous['lng']
            spot['geocodedFrom'] = previous.get('geocodedFrom', previous.get('address'))
            continue

        if spot.get('lat') and spot.get('lng'):
            continue

        print(f"  座標を引きます: {spot['name']}（{spot['address']}）")
        point = geocode(spot['address'])
        if point is None:
            print(f"    見つかりませんでした: {spot['name']}", file=sys.stderr)
            continue

        spot['lat'], spot['lng'] = point
        spot['geocodedFrom'] = normalise_address(spot['address'])
        geocoded += 1

    spots = [s for s in merged.values() if s.get('lat') and s.get('lng')]
    dropped = len(merged) - len(spots)
    spots.sort(key=lambda s: (s.get('area', ''), s['name']))

    OUTPUT.write_text(json.dumps({
        'note': 'キッチンカーが出店する場所（自治体・公園管理者が公表しているもの）。'
                '公式に確認できた事実のみを記録する。日々の出店予定は各自治体が毎月更新するため、'
                '当サイトでは持たず出典先へ案内する。座標は国土地理院の住所検索APIで取得した。',
        'asOf': f'{date.today().year}年{date.today().month}月{date.today().day}日 確認',
        'spots': spots,
    }, ensure_ascii=False, indent=1) + '\n', encoding='utf-8')

    areas = sorted({s.get('area', '') for s in spots})
    print()
    print(f'{len(spots)}件を書き出しました（今回引いた座標 {geocoded}件、座標が取れず除いた {dropped}件）')
    print(f'市区町村: {len(areas)}')
    for area in areas:
        count = sum(1 for s in spots if s.get('area') == area)
        print(f'  {area}: {count}件')


main()
