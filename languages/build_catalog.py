"""Rebuild the bundled WordPress POT/PO/MO catalogs without external dependencies."""
from pathlib import Path
import re, json, struct
ROOT = Path(__file__).resolve().parents[1]
entries = {}
literal = r"'((?:\\.|[^'\\])*)'"
for path in [ROOT / 'fandoogh-rest.php', *sorted((ROOT / 'src').rglob('*.php')), *sorted((ROOT / 'templates').rglob('*.php')), *sorted((ROOT / 'resources').rglob('*.php'))]:
    text = path.read_text(encoding='utf-8')
    patterns = [r"(?:__|esc_html__|esc_attr__|_e|esc_html_e|esc_attr_e)\(\s*" + literal + r"\s*,\s*'fandoogh-rest'"]
    # Reader intentionally routes static validation/exception messages through __($message).
    if path.name == 'Branches.php':
        patterns += [r"\$error\(\s*" + literal]
    if path.name == 'Reader.php':
        patterns += [r"self::error\(\s*" + literal, r"new \\RuntimeException\(\s*" + literal]
    if path.parent.name == 'Appearance' and path.name == 'CssCompiler.php':
        patterns += [r"\$this->fail\(\s*" + literal]
    if path.parent.name == 'Appearance' and path.name == 'Appearance.php':
        patterns += [r"self::error\(\s*[^,]+,\s*" + literal]
    for pattern in patterns:
        for match in re.finditer(pattern, text):
            message = match[1].replace("\\'", "'").replace('\\\\', '\\')
            entries.setdefault(message, set()).add(f'{path.relative_to(ROOT).as_posix()}:{text[:match.start()].count(chr(10))+1}')
builder_path = ROOT / 'resources/builder-block.js'
builder_source = builder_path.read_text(encoding='utf-8')
builder_messages = []
for match in re.finditer(r'\bt\(\s*' + literal, builder_source):
    message = match[1].replace("\\'", "'").replace('\\\\', '\\')
    entries.setdefault(message, set()).add(f'resources/builder-block.js:{builder_source[:match.start()].count(chr(10))+1}')
    if message not in builder_messages: builder_messages.append(message)
ui_path = ROOT / 'frontend/src/i18n.js'
ui_source = ui_path.read_text(encoding='utf-8')
ui_array = re.search(r'export const messages\s*=\s*(\[.*?\]);', ui_source, re.S)[1]
ui_messages = json.loads(re.sub(r',\s*\]', ']', ui_array))
for message in ui_messages:
    line = ui_source[:ui_source.index(json.dumps(message, ensure_ascii=False))].count('\n') + 1
    entries.setdefault(message, set()).add(f'frontend/src/i18n.js:{line}')
translations = json.loads((ROOT / 'languages/fa_IR.json').read_text(encoding='utf-8'))
translations.update({message: message for message in ui_messages})
missing = sorted(set(entries) - set(translations))
if missing:
    raise SystemExit('Missing Persian translations: ' + json.dumps(missing, ensure_ascii=False, indent=2))
header = ('Project-Id-Version: Fandoogh Rest 1.5.0\nReport-Msgid-Bugs-To: \n'
          'POT-Creation-Date: 2026-10-09 00:00+0000\nPO-Revision-Date: 2026-10-09 00:00+0000\n'
          'Last-Translator: Fandoogh Rest\nLanguage-Team: Persian\nLanguage: fa_IR\n'
          'MIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\n'
          'Plural-Forms: nplurals=2; plural=(n > 1);\nX-Domain: fandoogh-rest\n')
quote = lambda value: json.dumps(value, ensure_ascii=False)
english_ui = json.loads((ROOT / 'languages/en_US.json').read_text(encoding='utf-8'))
missing_english = sorted(set(ui_messages) - set(english_ui))
if missing_english:
    raise SystemExit('Missing English UI translations: ' + json.dumps(missing_english, ensure_ascii=False, indent=2))
english = {message: english_ui[message] if message in english_ui else message for message in entries}
for kind, locale, translated in [('pot', '', {}), ('po', 'fa_IR', translations), ('po', 'en_US', english)]:
    local_header = header.replace('Language: fa_IR', 'Language: ' + locale).replace('Language-Team: Persian', 'Language-Team: ' + ('English' if locale == 'en_US' else 'Persian'))
    if locale == 'en_US': local_header = local_header.replace('plural=(n > 1)', 'plural=(n != 1)')
    lines = ['# Fandoogh Rest translation catalog.', 'msgid ""', 'msgstr ' + quote(local_header)]
    for message in sorted(entries):
        lines += ['', '#: ' + ' '.join(sorted(entries[message]))]
        if re.search(r'%(?:[0-9]+\$)?[sd]', message): lines.append('#, php-format')
        lines += ['msgid ' + quote(message), 'msgstr ' + quote(translated[message] if kind == 'po' else '')]
    (ROOT / 'languages' / ('fandoogh-rest.pot' if kind == 'pot' else f'fandoogh-rest-{locale}.po')).write_text('\n'.join(lines)+'\n', encoding='utf-8')
    if kind == 'pot': continue
    catalog = {'': local_header, **{key: translated[key] for key in entries}}
    keys = sorted(catalog)
    ids = [key.encode('utf-8') for key in keys]
    values = [catalog[key].encode('utf-8') for key in keys]
    count = len(keys); offset = 28 + count * 16
    id_table = []; id_blob = b''
    for item in ids:
        id_table.append((len(item), offset + len(id_blob))); id_blob += item + b'\0'
    value_offset = offset + len(id_blob); value_table = []; value_blob = b''
    for item in values:
        value_table.append((len(item), value_offset + len(value_blob))); value_blob += item + b'\0'
    data = struct.pack('<7I', 0x950412de, 0, count, 28, 28 + count * 8, 0, 0)
    data += b''.join(struct.pack('<2I', *row) for row in id_table + value_table) + id_blob + value_blob
    (ROOT / f'languages/fandoogh-rest-{locale}.mo').write_bytes(data)
    jed = {'translation-revision-date': '2026-10-06 00:00+0000', 'generator': 'Fandoogh Rest', 'domain': 'messages', 'locale_data': {'messages': {'': {'domain': 'messages', 'lang': locale, 'plural-forms': 'nplurals=2; plural=' + ('(n > 1);' if locale == 'fa_IR' else '(n != 1);')}, **{message: [translated[message]] for message in builder_messages}}}}
    (ROOT / f'languages/fandoogh-rest-{locale}-admincafe-block.json').write_text(json.dumps(jed, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
    print(f'{locale}: {len(entries)} messages, 100% translated; MO {len(data)} bytes.')
