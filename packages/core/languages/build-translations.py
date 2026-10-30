#!/usr/bin/env python3
"""
Generate .po and .mo files from edminboost-admin-notice-manager.pot.

Requires: pip install polib

Backends (set BACKEND=google, BACKEND=mymemory, or BACKEND=argos):
  google (default) — no extra deps; uses translate.googleapis.com (gtx)
  pip install deep-translator  — MyMemory API (BACKEND=mymemory)
  pip install argostranslate   — offline (BACKEND=argos; needs compatible Python)

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

PLUGIN_SLUG = "edminboost-admin-notice-manager"
LANGUAGES_DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(LANGUAGES_DIR))

BACKEND = os.environ.get("BACKEND", "google").strip().lower()

if BACKEND == "mymemory":
	from deep_translator import MyMemoryTranslator
	from deep_translator.exceptions import TooManyRequests
elif BACKEND == "argos":
	from argos_backend import translate as offline_translate  # noqa: E402
elif BACKEND == "google":
	from google_backend import translate_many as offline_translate_many  # noqa: E402
else:
	raise SystemExit(f"Unknown BACKEND={BACKEND!r}")
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


def translate_batch(texts: list[str], target: str, retries: int = 8) -> list[str]:
	if not texts:
		return []
	translator = MyMemoryTranslator(source="en-GB", target=target)
	last_error = None
	for attempt in range(retries):
		try:
			results = translator.translate_batch(texts)
			if results and len(results) == len(texts):
				return list(results)
		except TooManyRequests as exc:
			last_error = exc
			time.sleep(min(120, 20 * (attempt + 1)))
		except Exception as exc:  # noqa: BLE001 — CLI tool
			last_error = exc
			time.sleep(3.0 * (attempt + 1))
		time.sleep(2.0)
	if last_error:
		print(f"  batch failed ({last_error}); falling back to single-string mode", flush=True)
		out: list[str] = []
		for text in texts:
			out.append(translate_single(text, target))
			time.sleep(4.0)
		return out
	return texts


def translate_single(text: str, target: str, retries: int = 10) -> str:
	last_error = None
	translator = MyMemoryTranslator(source="en-GB", target=target)
	for attempt in range(retries):
		try:
			result = translator.translate(text)
			if result:
				return result
		except TooManyRequests as exc:
			last_error = exc
			time.sleep(min(180, 30 * (attempt + 1)))
		except Exception as exc:  # noqa: BLE001 — CLI tool
			last_error = exc
			time.sleep(5.0 * (attempt + 1))
	return text if last_error else text


def translate_entry(msgid: str, target: str | None, translated: str) -> str:
	if target is None or should_skip_translation(msgid):
		return msgid
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

	pending: list[polib.POEntry] = []
	pending_ids: list[str] = []
	for entry in po:
		if not entry.msgid:
			continue
		if entry.msgstr and entry.msgstr.strip():
			continue
		if target is None or should_skip_translation(entry.msgid):
			entry.msgstr = entry.msgid
			if entry.msgid_plural:
				entry.msgstr_plural = {0: entry.msgid_plural, 1: entry.msgid_plural}
			continue
		pending.append(entry)
		if entry.msgid not in pending_ids:
			pending_ids.append(entry.msgid)

	total = len([e for e in po if e.msgid])
	unique_to_translate = [m for m in pending_ids if not should_skip_translation(m)]
	translated_map: dict[str, str] = {}
	if BACKEND == "argos" and target is not None:
		from argos_backend import translate as offline_translate  # noqa: WPS433

		for index, msgid in enumerate(unique_to_translate, start=1):
			raw_text = offline_translate(msgid, locale)
			translated_map[msgid] = translate_entry(msgid, target, raw_text)
			if index % 25 == 0:
				print(f"  {locale}: {index}/{len(unique_to_translate)}", flush=True)
				po.save(str(po_path))
			time.sleep(0.35)
	elif BACKEND == "google" and target is not None:
		batch_size = 18
		for start in range(0, len(unique_to_translate), batch_size):
			chunk = unique_to_translate[start : start + batch_size]
			try:
				raw_list = offline_translate_many(chunk, locale)
			except Exception as exc:  # noqa: BLE001 — CLI tool
				print(f"  {locale}: batch error ({exc}); retrying per string", flush=True)
				raw_list = []
				for msgid in chunk:
					from google_backend import translate as google_one  # noqa: WPS433

					try:
						raw_list.append(google_one(msgid, locale))
					except Exception:
						raw_list.append(msgid)
					time.sleep(2.5)
			for msgid, raw_text in zip(chunk, raw_list):
				translated_map[msgid] = translate_entry(msgid, target, raw_text)
			done = min(start + batch_size, len(unique_to_translate))
			print(f"  {locale}: {done}/{len(unique_to_translate)}", flush=True)
			po.save(str(po_path))
			time.sleep(4.0)
	else:
		batch_size = 12
		for start in range(0, len(unique_to_translate), batch_size):
			chunk = unique_to_translate[start : start + batch_size]
			raw = translate_batch(chunk, target) if target else chunk
			for msgid, raw_text in zip(chunk, raw):
				translated_map[msgid] = translate_entry(msgid, target, raw_text)
			done = min(start + batch_size, len(unique_to_translate))
			print(f"  {locale}: batch {done}/{len(unique_to_translate)}", flush=True)
			po.save(str(po_path))
			time.sleep(3.5)

	for entry in pending:
		entry.msgstr = translated_map.get(entry.msgid, entry.msgid)
		if entry.msgid_plural:
			plural_text = translated_map.get(entry.msgid_plural, entry.msgid_plural)
			entry.msgstr_plural = {0: plural_text, 1: plural_text}

	po.save(str(po_path))
	subprocess.run(["msgfmt", "-o", str(mo_path), str(po_path)], check=True)
	print(f"Built {po_path.name} and {mo_path.name}")


def locale_is_complete(locale: str) -> bool:
	po_path = LANGUAGES_DIR / f"{PLUGIN_SLUG}-{locale}.po"
	mo_path = LANGUAGES_DIR / f"{PLUGIN_SLUG}-{locale}.mo"
	if not po_path.is_file() or not mo_path.is_file():
		return False
	po = polib.pofile(str(po_path))
	empty = sum(1 for e in po if e.msgid and not (e.msgstr and e.msgstr.strip()))
	return empty == 0


def main() -> int:
	if not POT_PATH.is_file():
		print(f"Missing {POT_PATH}", file=sys.stderr)
		return 1

	only = os.environ.get("LOCALES", "").strip()
	selected = list(LOCALES.keys())
	if only:
		selected = [code.strip() for code in only.split(",") if code.strip() in LOCALES]

	for locale in selected:
		target, label = LOCALES[locale]
		if not only and locale_is_complete(locale):
			print(f"Skip {locale} (complete)")
			continue
		print(f"Translating {label}…")
		build_locale(locale, target, label)

	return 0


if __name__ == "__main__":
	sys.exit(main())
