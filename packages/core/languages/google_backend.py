"""Unofficial Google Translate (gtx) client for local PO generation."""

from __future__ import annotations

import json
import time
import urllib.error
import urllib.parse
import urllib.request

LOCALE_TO_GOOGLE: dict[str, str] = {
	"hi_IN": "hi",
	"ru_RU": "ru",
	"es_ES": "es",
	"zh_CN": "zh-CN",
	"zh_TW": "zh-TW",
	"fr_FR": "fr",
	"ar": "ar",
	"bn_BD": "bn",
}

BATCH_SEPARATOR = "\n\u241e\n"


def _request(text: str, target: str, retries: int = 10) -> str:
	params = urllib.parse.urlencode(
		{
			"client": "gtx",
			"sl": "en",
			"tl": target,
			"dt": "t",
			"q": text,
		}
	)
	url = f"https://translate.googleapis.com/translate_a/single?{params}"
	last_error: Exception | None = None
	for attempt in range(retries):
		try:
			req = urllib.request.Request(
				url,
				headers={"User-Agent": "Mozilla/5.0 (compatible; EdminBoostANM-i18n/1.0)"},
			)
			with urllib.request.urlopen(req, timeout=90) as resp:
				payload = json.loads(resp.read().decode("utf-8"))
			if payload and payload[0]:
				return "".join(part[0] for part in payload[0] if part and part[0])
		except urllib.error.HTTPError as exc:
			last_error = exc
			if exc.code == 429:
				time.sleep(min(180, 25 * (attempt + 1)))
			else:
				time.sleep(5.0 * (attempt + 1))
		except Exception as exc:  # noqa: BLE001 — CLI tool
			last_error = exc
			time.sleep(5.0 * (attempt + 1))
	if last_error:
		raise last_error
	return text


def translate(text: str, locale: str) -> str:
	target = LOCALE_TO_GOOGLE.get(locale, locale.split("_")[0])
	return _request(text, target)


def translate_many(texts: list[str], locale: str) -> list[str]:
	if not texts:
		return []
	if len(texts) == 1:
		return [translate(texts[0], locale)]
	target = LOCALE_TO_GOOGLE.get(locale, locale.split("_")[0])
	combined = BATCH_SEPARATOR.join(texts)
	translated = _request(combined, target)
	parts = translated.split(BATCH_SEPARATOR)
	if len(parts) == len(texts):
		return parts
	# Fallback: one request per string if batch split failed.
	return [translate(text, locale) for text in texts]
