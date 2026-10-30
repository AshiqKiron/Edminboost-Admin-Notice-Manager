"""Offline translation via Argos Translate (optional dependency)."""

from __future__ import annotations

import argostranslate.package
import argostranslate.translate

# WordPress locale => Argos target language code (from English).
LOCALE_TO_ARGOS: dict[str, str] = {
	"hi_IN": "hi",
	"ru_RU": "ru",
	"es_ES": "es",
	"zh_CN": "zh",
	"zh_TW": "zh",  # Argos has no zh-TW; Simplified output (see opencc note in README).
	"fr_FR": "fr",
	"ar": "ar",
	"bn_BD": "bn",
}

_installed: set[str] = set()


def ensure_pair(to_code: str) -> None:
	if to_code in _installed:
		return
	argostranslate.package.update_package_index()
	available = argostranslate.package.get_available_packages()
	package = next(
		(p for p in available if p.from_code == "en" and p.to_code == to_code),
		None,
	)
	if package is None:
		raise RuntimeError(f"No Argos package for en -> {to_code}")
	download_path = package.download()
	argostranslate.package.install_from_path(download_path)
	_installed.add(to_code)


def translate(text: str, locale: str) -> str:
	to_code = LOCALE_TO_ARGOS.get(locale)
	if not to_code:
		return text
	ensure_pair(to_code)
	return argostranslate.translate.translate(text, "en", to_code)
