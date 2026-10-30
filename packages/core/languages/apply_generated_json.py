#!/usr/bin/env python3
"""Merge generated/<locale>.json maps into .po files and compile .mo files."""

from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

import polib

PLUGIN_SLUG = "edminboost-admin-notice-manager"
LANGUAGES_DIR = Path(__file__).resolve().parent
GENERATED_DIR = LANGUAGES_DIR / "generated"
POT_PATH = LANGUAGES_DIR / f"{PLUGIN_SLUG}.pot"

LOCALE_LABELS = {
	"hi_IN": "Hindi",
	"ru_RU": "Russian",
	"es_ES": "Spanish",
	"zh_CN": "Chinese (Simplified, Mandarin)",
	"zh_TW": "Chinese (Traditional)",
	"fr_FR": "French",
	"ar": "Arabic",
}


def apply_locale(locale: str) -> None:
	json_path = GENERATED_DIR / f"{locale}.json"
	if not json_path.is_file():
		print(f"Skip {locale}: missing {json_path.name}", file=sys.stderr)
		return

	mapping = json.loads(json_path.read_text(encoding="utf-8"))
	po_path = LANGUAGES_DIR / f"{PLUGIN_SLUG}-{locale}.po"
	mo_path = LANGUAGES_DIR / f"{PLUGIN_SLUG}-{locale}.mo"

	if po_path.is_file():
		po = polib.pofile(str(po_path))
	else:
		po = polib.pofile(str(POT_PATH))
		po.metadata["Language"] = locale
		label = LOCALE_LABELS.get(locale, locale)
		po.metadata["Language-Team"] = f"{label} ({locale})"

	for entry in po:
		if entry.msgid and entry.msgid in mapping:
			entry.msgstr = mapping[entry.msgid]

	po.save(str(po_path))
	subprocess.run(["msgfmt", "-o", str(mo_path), str(po_path)], check=True)
	print(f"Applied {json_path.name} -> {po_path.name}, {mo_path.name}")


def main() -> int:
	if not POT_PATH.is_file():
		print(f"Missing {POT_PATH}", file=sys.stderr)
		return 1

	for json_path in sorted(GENERATED_DIR.glob("*.json")):
		if json_path.name == "msgids.json":
			continue
		apply_locale(json_path.stem)

	return 0


if __name__ == "__main__":
	sys.exit(main())
