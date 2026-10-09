"""Build customer-only Chinese/Turkish gettext files from the shared UI/server catalogs."""
from pathlib import Path
import json, re, struct

ROOT = Path(__file__).resolve().parents[1]
ui = json.loads((ROOT / 'resources/customer-strings.json').read_text(encoding='utf-8'))
server = json.loads((ROOT / 'resources/customer-server-strings.json').read_text(encoding='utf-8'))
catalogs = {code: {**ui[code], **server[code]} for code in ['fa', 'en', 'zh', 'tr']}
for code, strings in catalogs.items():
    assert set(strings) == set(catalogs['fa']), (code, 'missing customer messages')
    for message, translated in strings.items():
        assert isinstance(translated, str) and translated, (code, message)
        assert sorted(re.findall(r'\{[a-z_]+\}', message)) == sorted(re.findall(r'\{[a-z_]+\}', translated)), (code, message, 'placeholder mismatch')

for code, locale, plurals in [('zh', 'zh_CN', 'nplurals=1; plural=0;'), ('tr', 'tr_TR', 'nplurals=2; plural=(n > 1);')]:
    header = f'Project-Id-Version: Fandoogh Rest 1.5.0\nLanguage: {locale}\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\nPlural-Forms: {plurals}\n'
    entries = {'': header, **catalogs[code]}
    lines = ['# Fandoogh Rest customer interface. The management panel stays Persian.', 'msgid ""', 'msgstr ' + json.dumps(header)]
    for message in sorted(catalogs[code]):
        source = 'resources/customer-server-strings.json' if message in server[code] else 'frontend/src/customer-i18n.js'
        lines += ['', '#: ' + source, 'msgid ' + json.dumps(message, ensure_ascii=False), 'msgstr ' + json.dumps(catalogs[code][message], ensure_ascii=False)]
    (ROOT / f'languages/fandoogh-rest-{locale}.po').write_text('\n'.join(lines) + '\n', encoding='utf-8')
    keys = sorted(entries)
    ids = [key.encode('utf-8') for key in keys]
    values = [entries[key].encode('utf-8') for key in keys]
    count = len(keys)
    offset = 28 + count * 16
    id_table, id_blob = [], b''
    for item in ids:
        id_table.append((len(item), offset + len(id_blob)))
        id_blob += item + b'\0'
    value_table, value_blob = [], b''
    for item in values:
        value_table.append((len(item), offset + len(id_blob) + len(value_blob)))
        value_blob += item + b'\0'
    data = struct.pack('<7I', 0x950412de, 0, count, 28, 28 + count * 8, 0, 0)
    data += b''.join(struct.pack('<2I', *row) for row in id_table + value_table) + id_blob + value_blob
    (ROOT / f'languages/fandoogh-rest-{locale}.mo').write_bytes(data)
    print(f'{locale}: {len(catalogs[code])} customer messages; {len(data)} MO bytes.')
