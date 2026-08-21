"""PDF から文字を取り出す。外部ライブラリなしで。

poppler も pypdf も入っていない環境で動かすため、オブジェクトを拾い、
フォントの ToUnicode（CMap）で文字コードを文字に直しながら、
テキスト表示の命令だけを読む。

単体で動かすとき: python scripts/lib/pdf_text.py file.pdf
"""
import re
import sys
import zlib


def objects(data: bytes) -> dict[int, bytes]:
    """`N 0 obj ... endobj` を拾う。オブジェクト番号 -> 中身。"""
    found = {}

    for match in re.finditer(rb'(\d+)\s+(\d+)\s+obj\b', data):
        number = int(match.group(1))
        start = match.end()
        end = data.find(b'endobj', start)
        if end < 0:
            continue
        found[number] = data[start:end]

    return found


def object_streams(data: bytes, raw: dict[int, bytes]) -> dict[int, bytes]:
    """圧縮オブジェクト（/Type /ObjStm）の中身も展開して足す。"""
    extra = {}

    for number, body in raw.items():
        if b'/ObjStm' not in body:
            continue

        content = stream_of(body)
        if content is None:
            continue

        n_match = re.search(rb'/N\s+(\d+)', body)
        first_match = re.search(rb'/First\s+(\d+)', body)
        if not n_match or not first_match:
            continue

        count = int(n_match.group(1))
        first = int(first_match.group(1))
        header = content[:first].split()

        for index in range(count):
            try:
                obj_number = int(header[index * 2])
                offset = int(header[index * 2 + 1])
            except (IndexError, ValueError):
                break
            end = first + (int(header[index * 2 + 3]) if index * 2 + 3 < len(header) else len(content) - first)
            extra[obj_number] = content[first + offset:end]

    return extra


def stream_of(body: bytes) -> bytes | None:
    """オブジェクトの stream 部分を取り出して、必要なら展開する。"""
    start = body.find(b'stream')
    if start < 0:
        return None

    start += len(b'stream')
    if body[start:start + 2] == b'\r\n':
        start += 2
    elif body[start:start + 1] in (b'\n', b'\r'):
        start += 1

    end = body.find(b'endstream', start)
    payload = body[start:end if end > 0 else len(body)]

    if b'/FlateDecode' in body[:start]:
        try:
            return zlib.decompress(payload)
        except zlib.error:
            try:
                return zlib.decompressobj().decompress(payload)
            except zlib.error:
                return None

    return payload


def parse_tounicode(text: bytes) -> dict[int, str]:
    """ToUnicode CMap から、文字コード -> 文字 の対応表を作る。"""
    table: dict[int, str] = {}
    body = text.decode('latin-1', 'replace')

    for block in re.findall(r'beginbfchar(.*?)endbfchar', body, re.S):
        for src, dst in re.findall(r'<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>', block):
            table[int(src, 16)] = decode_utf16(dst)

    for block in re.findall(r'beginbfrange(.*?)endbfrange', body, re.S):
        # <lo> <hi> <dst> の形
        for lo, hi, dst in re.findall(r'<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>', block):
            start, stop, base = int(lo, 16), int(hi, 16), int(dst, 16)
            for offset in range(min(stop - start + 1, 65536)):
                table[start + offset] = chr(base + offset)
        # <lo> <hi> [<dst> <dst> ...] の形
        for lo, _hi, items in re.findall(r'<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*\[(.*?)\]', block, re.S):
            start = int(lo, 16)
            for offset, dst in enumerate(re.findall(r'<([0-9A-Fa-f]+)>', items)):
                table[start + offset] = decode_utf16(dst)

    return table


def decode_utf16(hex_text: str) -> str:
    raw = bytes.fromhex(hex_text if len(hex_text) % 2 == 0 else '0' + hex_text)
    try:
        return raw.decode('utf-16-be')
    except UnicodeDecodeError:
        return raw.decode('latin-1', 'replace')


def font_maps(all_objects: dict[int, bytes]) -> dict[str, dict[int, str]]:
    """ページ内のフォント名 -> 対応表。名前は /F1 のような指定。"""
    unicode_maps: dict[int, dict[int, str]] = {}

    for number, body in all_objects.items():
        if b'/ToUnicode' not in body:
            continue
        reference = re.search(rb'/ToUnicode\s+(\d+)\s+\d+\s+R', body)
        if not reference:
            continue
        target = int(reference.group(1))
        if target not in all_objects:
            continue
        content = stream_of(all_objects[target])
        if content:
            unicode_maps[number] = parse_tounicode(content)

    # /Font << /F1 12 0 R >> の対応を集める
    by_name: dict[str, dict[int, str]] = {}
    for body in all_objects.values():
        for block in re.findall(rb'/Font\s*<<(.*?)>>', body, re.S):
            for name, ref in re.findall(rb'/([A-Za-z0-9#+.\-]+)\s+(\d+)\s+\d+\s+R', block):
                target = int(ref)
                if target in unicode_maps:
                    by_name[name.decode('latin-1')] = unicode_maps[target]

    return by_name


def text_of(content: bytes, fonts: dict[str, dict[int, str]]) -> str:
    """内容ストリームから、表示される文字を順に取り出す。"""
    out = []
    current: dict[int, str] = {}
    body = content.decode('latin-1', 'replace')

    token = re.compile(r'/([A-Za-z0-9#+.\-]+)\s+[\d.]+\s+Tf|\((?:\\.|[^\\()])*\)|<([0-9A-Fa-f\s]*)>|(TJ|Tj|TD|Td|T\*|ET)')

    for match in token.finditer(body):
        font, hex_text, operator = match.group(1), match.group(2), match.group(3)

        if font:
            current = fonts.get(font, {})
            continue

        if operator in ('TD', 'Td', 'T*', 'ET'):
            out.append('\n')
            continue

        if hex_text is not None:
            digits = re.sub(r'\s', '', hex_text)
            if current:
                # 2バイトコードとして読む
                for index in range(0, len(digits) - 3, 4):
                    code = int(digits[index:index + 4], 16)
                    out.append(current.get(code, ''))
            else:
                out.append(decode_utf16(digits))
            continue

        literal = match.group(0)
        if literal.startswith('('):
            inner = literal[1:-1]
            inner = re.sub(r'\\([()\\])', r'\1', inner)
            if current:
                raw = inner.encode('latin-1', 'replace')
                for index in range(0, len(raw) - 1, 2):
                    code = (raw[index] << 8) | raw[index + 1]
                    out.append(current.get(code, ''))
            else:
                out.append(inner)

    return ''.join(out)


def extract(data: bytes) -> str:
    """PDF のバイト列から、本文を取り出す。"""
    raw = objects(data)
    raw.update(object_streams(data, raw))
    fonts = font_maps(raw)

    pieces = []
    for _number, body in sorted(raw.items()):
        if b'/ObjStm' in body or b'/ToUnicode' in body[:60]:
            continue
        content = stream_of(body)
        if not content or (b'Tj' not in content and b'TJ' not in content):
            continue
        pieces.append(text_of(content, fonts))

    text = '\n'.join(pieces)

    # 言語タグが混ざるので落とし、空白を整える。
    text = re.sub(r'(en-US|ja-JP|zh-TW|zh-CN|ko-KR)', '', text)
    text = re.sub(r'[ \t]+', ' ', text)
    text = re.sub(r'\n\s*\n+', '\n', text)

    return text


if __name__ == '__main__':
    print(extract(open(sys.argv[1], 'rb').read()))
