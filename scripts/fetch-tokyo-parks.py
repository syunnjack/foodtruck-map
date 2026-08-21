"""都立公園のキッチンカー出店予定公園を、東京都公園協会の募集要項から取り出す。

出典: 公益財団法人 東京都公園協会「2026年度 都立公園キッチンカー（移動販売車）
      出店事業者募集要項」（8〜9ページの出店予定公園一覧）
      https://www.tokyo-park.or.jp/association/foodtruck_01.pdf

一覧には公園名・郵便番号・住所と、土日祝に出店するか、イベント時のみかが
載っている。書かれている事実だけを取る。出店日そのものは協会が個別に
調整するため、この資料には無い。

使い方: python scripts/fetch-tokyo-parks.py > /tmp/tokyo-parks.json
"""
import json
import re
import sys
import urllib.request
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent / 'lib'))

import pdf_text  # noqa: E402

PDF_URL = 'https://www.tokyo-park.or.jp/association/foodtruck_01.pdf'
SOURCE_LABEL = '公益財団法人 東京都公園協会 募集要項'
UA = ('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
      '(KHTML, like Gecko) Chrome/131.0 Safari/537.36')

MARKS = '〇○'          # 出店ありを表す丸（資料内で2種類が混在している）
BLANK = '－-—'          # 出店なしを表すダッシュ


def download() -> bytes:
    cache = Path(__file__).resolve().parent / '.cache' / 'tokyo-park.pdf'

    if cache.exists():
        return cache.read_bytes()

    request = urllib.request.Request(PDF_URL, headers={'User-Agent': UA})
    with urllib.request.urlopen(request, timeout=120) as response:
        data = response.read()

    cache.parent.mkdir(parents=True, exist_ok=True)
    cache.write_bytes(data)

    return data


def table(text: str) -> str:
    """一覧の部分だけを、空白を詰めた1行にして返す。"""
    start = text.rfind('出店予定公園一覧')

    if start < 0:
        raise SystemExit('出店予定公園一覧が見つかりません。資料の作りが変わった可能性があります。')

    end = text.index('※上記以外の公園', start)
    block = re.sub(r'\s+', '', text[start:end])

    for noise in ('出店予定公園一覧', 'No', '公園名', '住所', '出店予定日', '土日祝日', 'ｲﾍﾞﾝﾄ時'):
        block = block.replace(noise, '')

    return block


def municipality(address: str) -> str:
    """住所から市区町村を切り出す。

    「東村山市」のように名前の中に「村」を含むものがあるので、
    先に区・市を探し、無いときだけ町・村を見る。
    """
    head = address.replace('東京都', '', 1)

    for suffix in ('区', '市'):
        at = head.find(suffix)
        if 0 < at < 10:
            return '東京都' + head[:at + 1]

    for suffix in ('町', '村'):
        at = head.find(suffix)
        if 0 < at < 10:
            return '東京都' + head[:at + 1]

    return '東京都'


def rows(block: str) -> list[dict]:
    """「番号 公園名 〒郵便番号 住所 土日祝 イベント」の繰り返しを読む。

    住所にもハイフンや漢数字が入るので、区切りは郵便番号の「〒」と、
    丸印（住所には出てこない）を手がかりにする。
    """
    chunks = block.split('〒')
    found = []

    # 最初の塊は 1件目の番号と公園名。以降は「郵便番号+住所+丸印+次の番号と公園名」。
    name = re.sub(r'^\d{1,2}', '', chunks[0])

    for chunk in chunks[1:]:
        match = re.match(r'(\d{3})-(\d{4})(.+)', chunk)
        if not match:
            continue

        postcode = f'{match.group(1)}-{match.group(2)}'
        rest = match.group(3)

        # 丸印は住所には現れない。最後の丸印がイベント欄。
        marks = [i for i, char in enumerate(rest) if char in MARKS]
        if not marks:
            continue

        event_at = marks[-1]
        before = rest[:event_at]

        # 土日祝の欄は、イベント欄の直前の1文字（丸・ダッシュ・※印）。
        weekend_char = before[-1] if before else ''
        if weekend_char in MARKS or weekend_char in BLANK or weekend_char == '※':
            address = before[:-1]
        else:
            weekend_char = ''
            address = before

        address = address.strip()

        found.append({
            'name': name.strip(),
            'postcode': postcode,
            'address': address,
            'weekends': weekend_char in MARKS,
            'note_mark': weekend_char == '※',
        })

        # 丸印のあとに続くのは、次の行の番号と公園名（間にページ番号が挟まることがある）。
        name = re.sub(r'^\d{1,3}', '', rest[event_at + 1:])

    return found


def main() -> None:
    text = pdf_text.extract(download())
    records = rows(table(text))

    spots = []
    for record in records:
        if not record['name'] or not record['address']:
            continue

        area = municipality(record['address'])

        note = ('土日祝日などの繁忙日に出店します。イベント時にも出店があります。'
                if record['weekends'] else
                'イベント開催時にキッチンカーが出店します。')

        if record['note_mark']:
            note += '（出店可能日のみ）'

        spots.append({
            'name': record['name'],
            'address': record['address'],
            'area': area,
            'hours': '午前10時〜午後4時30分',
            'note': f'東京都公園協会が管理する都立公園等です。{note}'
                    '営業時間は公園やイベントにより異なります。',
            'sourceUrl': PDF_URL,
            'sourceLabel': SOURCE_LABEL,
        })

    print(f'{len(spots)}件を取り出しました', file=sys.stderr)
    json.dump(spots, sys.stdout, ensure_ascii=False, indent=1)


main()
