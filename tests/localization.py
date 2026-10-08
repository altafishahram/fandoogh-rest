from pathlib import Path
import gettext, json, re, runpy
root = Path(__file__).resolve().parents[1]
scope = runpy.run_path(str(root / 'languages/build_catalog.py'))
entries = scope['entries']
catalogs = {}
for locale, source in [('fa_IR', scope['translations']), ('en_US', scope['english'])]:
    with (root / f'languages/admincafe-{locale}.mo').open('rb') as stream:
        catalogs[locale] = translations = gettext.GNUTranslations(stream)
    for message in entries:
        actual = translations.gettext(message)
        assert actual == source[message] and actual, (locale, message)
        assert re.findall(r'%(?:[0-9]+\$)?[sd]', message) == re.findall(r'%(?:[0-9]+\$)?[sd]', actual), (locale, message)
    assert translations.info()['language'] == locale
assert catalogs['fa_IR'].gettext('Product not found.') == 'محصول پیدا نشد.'
assert catalogs['fa_IR'].gettext('سبد شما') == 'سبد شما'
assert catalogs['en_US'].gettext('سبد شما') == 'Your cart'
assert catalogs['en_US'].gettext('Product not found.') == 'Product not found.'
assert 'Use a UTF-8 CSV file.' in entries  # Dynamic Reader::error messages are included.
assert 'Offline settlement recorded by user %d.' in entries
assert all('frontend/src/i18n.js:' in ' '.join(entries[message]) for message in scope['ui_messages'])
for locale, source in [('fa_IR', scope['translations']), ('en_US', scope['english'])]:
    jed = json.loads((root / f'languages/admincafe-{locale}-admincafe-block.json').read_text(encoding='utf-8'))
    assert jed['locale_data']['messages']['']['lang'] == locale
    for message in scope['builder_messages']:
        assert jed['locale_data']['messages'][message] == [source[message]]
assert 'Sign in | AdminCafe' in entries and 'templates/login.php:' in ' '.join(entries['Sign in | AdminCafe'])
assert 'Enable browser JavaScript to use the menu and panel.' in entries
print(f'Localization verification: {2*len(entries)} MO translations with placeholder parity + {2*len(scope["builder_messages"])} Jed strings + 13 catalog checks passed.')
