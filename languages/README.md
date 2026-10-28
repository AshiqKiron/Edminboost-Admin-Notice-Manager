# Translations

Text domain: `edminboost-admin-notice-manager`

WordPress loads compiled `.mo` files from this directory when the site language matches the locale suffix.

## Bundled locales

| WordPress locale | Language |
|------------------|----------|
| `en_US` | English (United States) |
| `bn_BD` | Bengali (Bangla) |
| `hi_IN` | Hindi |
| `ru_RU` | Russian |
| `es_ES` | Spanish |
| `fr_FR` | French |
| `ar` | Arabic |
| `zh_CN` | Chinese (Simplified, Mandarin) |
| `zh_TW` | Chinese (Traditional) |

Set **Settings → General → Site Language** to one of these locales (or install the matching WordPress language pack) to use the plugin in that language.

## Regenerate template (POT)

From the plugin root:

```bash
wp i18n make-pot . languages/edminboost-admin-notice-manager.pot --ignore-domain
```

The codebase uses the `EDMINBOOST_ANM_TEXT_DOMAIN` constant, so `--ignore-domain` is required when extracting strings.

## Rebuild PO/MO files

Optional dev dependency: `pip install polib deep-translator`

```bash
cd languages
python3 build-translations.py
```

Machine-assisted strings are a starting point; community corrections via `.po` files are welcome.
