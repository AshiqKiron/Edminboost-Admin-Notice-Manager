# Edminboost Admin Notice Manager — Agent Guide

WordPress plugin that intercepts, filters, and organizes admin notices from a central dashboard. PHP 7.4+, WordPress 6.0+, GPL-3.0. Version **1.1.0**.

## Quick Reference

| Item | Value |
|------|-------|
| Bootstrap | `edminboost-admin-notice-manager.php` |
| Slug / text domain | `edminboost-admin-notice-manager` |
| Class prefix | `EDMINBOOST_ANM_*` |
| Function prefix | `edminboost_anm_*` |
| Main option | `edminboost_anm_settings` |
| Other options | `edminboost_anm_rules`, `edminboost_anm_inbox`, `edminboost_anm_stats`, `edminboost_anm_version` |
| Capability | `manage_options` |
| AJAX nonce | `edminboost_anm_admin` |

## Architecture

```
edminboost-admin-notice-manager.php   # constants, requires, lifecycle hooks
includes/
  class-edminboost-anm-plugin.php     # hook orchestrator
  class-edminboost-anm-admin.php      # menus, settings UI, assets
  class-edminboost-anm-settings.php   # defaults, get, sanitize
  class-edminboost-anm-notices.php    # notice panel + styling
  class-edminboost-anm-interceptor.php
  class-edminboost-anm-inbox.php
  class-edminboost-anm-rules.php
  class-edminboost-anm-stats.php
  class-edminboost-anm-ajax.php
  class-edminboost-anm-importer.php
  class-edminboost-anm-digest.php
  class-edminboost-anm-webhook.php
  class-edminboost-anm-debug.php      # WP_DEBUG only
  class-edminboost-anm-{activator,deactivator}.php
admin/
  css/edminboost-anm-admin.css
  js/edminboost-anm-admin.js
  js/edminboost-anm-interceptor.js
  partials/edminboost-anm-{dashboard,inbox,rules,settings}-page.php
languages/  uninstall.php  readme.txt  composer.json
```

- Bootstrap defines constants and calls `edminboost_anm_run_plugin()`.
- `EDMINBOOST_ANM_Plugin::run()` wires i18n, admin menu/settings/assets, and on `init`: notices, interceptor, AJAX, digest.
- Settings: primary option via `EDMINBOOST_ANM_Settings` (Settings API group `edminboost_anm_settings_group`). Rules and inbox stored in separate options.
- Options API only — no custom tables. No REST API.
- AJAX for dashboard/rules/inbox/importer actions; weekly digest via WP-Cron; optional webhook (user URL, off by default).

## Admin Pages

Use `EDMINBOOST_ANM_Admin::PAGE_SLUG` — never hardcode slug strings.

| Page | Slug | Renderer |
|------|------|----------|
| Dashboard | `PAGE_SLUG` | `render_dashboard_page()` |
| Inbox | `PAGE_SLUG . '-inbox'` | `render_inbox_page()` |
| Rules | `PAGE_SLUG . '-rules'` | `render_rules_page()` |
| Settings | `PAGE_SLUG . '-settings'` | `render_settings_page()` |

## Extension Hooks

- `edminboost_anm_settings_defaults` — filter default settings
- `edminboost_anm_settings` — filter merged settings on read
- `edminboost_anm_sanitize_settings` — filter sanitized settings on save
- `edminboost_anm_notices_loaded` — after notice manager hooks register
- `edminboost_anm_upsell_preset_phrases` — filter upsell phrase preset list

## Cursor Rules

Persistent AI guidance lives in `.cursor/rules/`:

| Rule | Scope |
|------|-------|
| `edminboost-anm-project.mdc` | Always — architecture, naming, layout |
| `edminboost-anm-standards.mdc` | Always — i18n, UX, performance, release |
| `edminboost-anm-compliance.mdc` | Always — security and pre-ship checklist |
| `edminboost-anm-wordpress-php.mdc` | `**/*.php` — PHP conventions |
| `edminboost-anm-admin-assets.mdc` | `admin/**/*` — JS, CSS, partials |

## Change Discipline

- Admin-only plugin — no front-end hooks unless explicitly requested.
- Minimize diff scope; match existing file structure and style.
- Add `index.php` silence files when creating new directories.
- Update `readme.txt` changelog for user-facing changes.
- Update `uninstall.php` (and deactivator for cron) when adding stored options or schedules.
- New AJAX actions: register in `EDMINBOOST_ANM_Ajax`, use `verify_request()`, document in compliance rules if behavior is sensitive.
