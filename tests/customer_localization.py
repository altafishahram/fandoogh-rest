from pathlib import Path
import gettext, json, re, runpy

ROOT = Path(__file__).resolve().parents[1]
scope = runpy.run_path(str(ROOT / 'languages/build_customer_catalog.py'))
catalogs = scope['catalogs']
checked = 0
for code, locale in [('zh', 'zh_CN'), ('tr', 'tr_TR')]:
    with (ROOT / f'languages/fandoogh-rest-{locale}.mo').open('rb') as stream:
        catalog = gettext.GNUTranslations(stream)
    for key, value in catalogs[code].items():
        assert catalog.gettext(key) == value
        checked += 1
for key in ['An item is unavailable.', 'Your session has expired. Reload the menu and try again.', 'Toman']:
    assert all(catalogs[code][key] for code in ['fa', 'en', 'zh', 'tr'])
packs = json.loads((ROOT / 'languages/checkout/SOURCES.json').read_text(encoding='utf-8'))['packs']
assert len(packs) == 6
for pack in packs:
    assert pack['source'].startswith('https://downloads.wordpress.org/')
    name = ('woocommerce-' if pack['component'] == 'woocommerce' else '') + pack['locale'] + '.mo'
    with (ROOT / 'languages/checkout' / pack['component'] / name).open('rb') as stream:
        catalog = gettext.GNUTranslations(stream)
    assert catalog.info()['language'] in [pack['locale'], pack['locale'].split('_')[0]]
    if pack['component'] == 'woocommerce':
        assert catalog.gettext('Place order') != 'Place order'
print(f'Customer localization: {checked} MO values, four-language key/placeholder parity and six official language packs verified.')
