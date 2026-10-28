#!/usr/bin/env python3
"""
Generate .po and .mo files from edminboost-admin-notice-manager.pot.

Requires: pip install polib deep-translator

Regenerate POT (from plugin root):
  wp i18n make-pot . languages/edminboost-admin-notice-manager.pot --ignore-domain

Run one locale: LOCALES=hi_IN python3 build-translations.py
"""

from __future__ import annotations

import os
import re
import subprocess
import sys
import time
from pathlib import Path

import polib
from deep_translator import MyMemoryTranslator
from deep_translator.exceptions import TooManyRequests

PLUGIN_SLUG = "edminboost-admin-notice-manager"
LANGUAGES_DIR = Path(__file__).resolve().parent
POT_PATH = LANGUAGES_DIR / f"{PLUGIN_SLUG}.pot"

LOCALES = {
	"bn_BD": ("bn-IN", "Bengali (Bangla)"),
	"en_US": (None, "English (United States)"),
	"hi_IN": ("hi-IN", "Hindi"),
	"ru_RU": ("ru-RU", "Russian"),
	"es_ES": ("es-ES", "Spanish"),
	"zh_CN": ("zh-CN", "Chinese (Simplified, Mandarin)"),
	"zh_TW": ("zh-TW", "Chinese (Traditional)"),
	"fr_FR": ("fr-FR", "French"),
	"ar": ("ar-SA", "Arabic"),
}

URL_RE = re.compile(r"^https?://", re.I)
PLACEHOLDER_RE = re.compile(r"%(\d+\$)?[sd]")


def should_skip_translation(text: str) -> bool:
	if not text.strip():
		return True
	if URL_RE.match(text.strip()):
		return True
	if text.strip() in ("ashiquzzaman", "Edminboost", "ANM"):
		return True
	return False


def placeholders(text: str) -> list[str]:
	return PLACEHOLDER_RE.findall(text)


def translate_text(text: str, target: str, retries: int = 8) -> str:
	last_error = None
	for attempt in range(retries):
		try:
			result = MyMemoryTranslator(source="en-GB", target=target).translate(text)
			if result:
				return result
		except TooManyRequests as exc:
			last_error = exc
			time.sleep(min(90, 15 * (attempt + 1)))
		except Exception as exc:  # noqa: BLE001 — CLI tool
			last_error = exc
			time.sleep(2.0 * (attempt + 1))
		time.sleep(0.65)
	if last_error:
		raise last_error
	return text


def translate_entry(msgid: str, target: str | None) -> str:
	if target is None or should_skip_translation(msgid):
		return msgid
	translated = translate_text(msgid, target)
	if placeholders(msgid) != placeholders(translated):
		return msgid
	return translated


def apply_locale_metadata(po: polib.POFile, locale: str, label: str) -> None:
	po.metadata["Language"] = locale
	po.metadata["Language-Team"] = f"{label} ({locale})"
	if locale == "ar":
		po.metadata["Plural-Forms"] = (
			"nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : "
			"n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5);"
		)
	elif locale.startswith("zh"):
		po.metadata["Plural-Forms"] = "nplurals=1; plural=0;"
	else:
		po.metadata["Plural-Forms"] = po.metadata.get(
			"Plural-Forms", "nplurals=2; plural=(n != 1);"
		)


def build_locale(locale: str, target: str | None, label: str) -> None:
	po_path = LANGUAGES_DIR / f"{PLUGIN_SLUG}-{locale}.po"
	mo_path = LANGUAGES_DIR / f"{PLUGIN_SLUG}-{locale}.mo"

	if po_path.exists():
		po = polib.pofile(str(po_path))
	else:
		po = polib.pofile(str(POT_PATH))

	apply_locale_metadata(po, locale, label)

	cache: dict[str, str] = {}
	for existing in po:
		if existing.msgid and existing.msgstr and existing.msgstr.strip():
			cache[existing.msgid] = existing.msgstr

	def tr(msgid: str) -> str:
		if msgid in cache:
			return cache[msgid]
		cache[msgid] = translate_entry(msgid, target)
		return cache[msgid]

	total = len([e for e in po if e.msgid])
	done = 0
	for entry in po:
		if not entry.msgid:
			continue
		if entry.msgstr and entry.msgstr.strip():
			done += 1
			continue
		entry.msgstr = tr(entry.msgid)
		if entry.msgid_plural:
			plural_text = tr(entry.msgid_plural)
			entry.msgstr_plural = {0: plural_text, 1: plural_text}
		done += 1
		if done % 10 == 0:
			po.save(str(po_path))
			print(f"  {locale}: {done}/{total}", flush=True)
		if target is not None and not should_skip_translation(entry.msgid):
			time.sleep(0.65)

	po.save(str(po_path))
	subprocess.run(["msgfmt", "-o", str(mo_path), str(po_path)], check=True)
	print(f"Built {po_path.name} and {mo_path.name}")


def main() -> int:
	if not POT_PATH.is_file():
		print(f"Missing {POT_PATH}", file=sys.stderr)
		return 1

	only = os.environ.get("LOCALES", "").strip()
	selected = LOCALES.keys()
	if only:
		selected = [code.strip() for code in only.split(",") if code.strip() in LOCALES]

	for locale in selected:
		target, label = LOCALES[locale]
		mo_path = LANGUAGES_DIR / f"{PLUGIN_SLUG}-{locale}.mo"
		if mo_path.exists() and locale not in (only.split(",") if only else []):
			print(f"Skip {locale} (already built)")
			continue
		print(f"Translating {label}…")
		build_locale(locale, target, label)

	return 0


if __name__ == "__main__":
	sys.exit(main())
