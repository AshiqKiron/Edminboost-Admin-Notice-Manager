# Edminboost Admin Notice Manager — Agent Guide

WordPress plugin that intercepts, filters, and organizes admin notices from a central dashboard. PHP 7.4+, WordPress 6.0+, GPL-3.0. Version **1.1.1**.

## Quick Reference

| Item | Value |
|------|-------|
| Bootstrap | `edminboost-admin-notice-manager.php` |
| Slug / text domain | `edminboost-admin-notice-manager` |
| Class prefix | `EDMINBOOST_ANM_*` |
| Function prefix | `edminboost_anm_*` |
| Main option | `edminboost_anm_settings` |
| Other options | `edminboost_anm_rules`, `edminboost_anm_inbox`, `edminboost_anm_stats`, `edminboost_anm_version` |
| Multisite sitemeta | `edminboost_anm_network`, `edminboost_anm_network_settings`, `edminboost_anm_network_rules` |
| Capability | `manage_options` (site); `manage_network_options` (network admin) |
| AJAX nonce | `edminboost_anm_admin` |

## Architecture

```
edminboost-admin-notice-manager.php   # constants, requires, lifecycle hooks
includes/
  class-edminboost-anm-plugin.php     # hook orchestrator
  class-edminboost-anm-admin.php      # menus, inbox list helpers, assets
  class-edminboost-anm-settings.php   # defaults, get, sanitize
  class-edminboost-anm-notices.php    # admin bar panel (link/dropdown), inline hide assets
  class-edminboost-anm-interceptor.php
  class-edminboost-anm-inbox.php
  class-edminboost-anm-rules.php
  class-edminboost-anm-stats.php
  class-edminboost-anm-ajax.php
  class-edminboost-anm-importer.php
  class-edminboost-anm-digest.php
  class-edminboost-anm-webhook.php
  class-edminboost-anm-ad-redirects.php  # block unwanted admin redirects
  class-edminboost-anm-multisite.php  # network policy, defaults, shared rules
  class-edminboost-anm-debug.php      # WP_DEBUG only
  class-edminboost-anm-{activator,deactivator}.php
admin/
  css/edminboost-anm-admin.css
  js/edminboost-anm-admin.js
  js/edminboost-anm-interceptor.js
  js/edminboost-anm-notices.js
  js/edminboost-anm-notice-inline.js
  partials/edminboost-anm-{dashboard,inbox,rules,settings}-page.php
  partials/edminboost-anm-inbox-table-rows.php
  partials/edminboost-anm-notice-panel-dropdown.php
  partials/edminboost-anm-network-policy-page.php
languages/  # .pot, locale .po/.mo; optional build-translations.py
uninstall.php  readme.txt  composer.json
```

- Bootstrap defines constants, requires multisite helper, and calls `edminboost_anm_run_plugin()`.
- `EDMINBOOST_ANM_Plugin::run()` wires i18n, site + network admin menu/settings/assets, `wp_initialize_site` seeding, and on `init`: notices, interceptor, AJAX, digest, ad redirects.
- Settings: primary option via `EDMINBOOST_ANM_Settings` (Settings API group `edminboost_anm_settings_group`). Rules and inbox stored in separate per-site options. Tabbed settings UI (General, Notices, Access, Admin bar, Filtering, Security, Notifications, White label, Migration; **Multisite** tab on multisite sites). Network admin: multisite policy, network default settings (same partial), network auto-rules.
- **Multisite**: policies `per_site` / `network_defaults` / `network_locked`; merged effective settings; optional shared network auto-rules; site edit gates via `site_can_edit_settings()` / `site_can_edit_rules()`.
- **White label**: agency menu title, dashboard brand/logo, docs link overrides (`settings.white_label`).
- Options API only — no custom tables. No REST API.
- AJAX for dashboard/rules/inbox (including `edminboost_anm_inbox_list` sort; inbox snooze toggle and read state), importer, capture, WP preset conversion, user search, notice hide/reset hidden; weekly digest via WP-Cron (`send_weekday`, custom `weekly` schedule); optional webhook (user URL preserved when disabled, off by default).
- Auto-Rules: `keyword`, `regex`, and `css` selector match types; custom upsell blacklist phrases apply even when Kill All Upsells preset is off.
- **Ad redirects**: optional `block_ad_redirects` URL blocklist — admin-only redirect filtering via `EDMINBOOST_ANM_Ad_Redirects`.
- **Inline notice controls**: `edminboost-anm-notice-inline.js` on non–plugin-screen wp-admin for “Hide for me” / “Hide for all”.
- **Notifications previews**: Settings Notifications tab — two-column digest form + sticky structured email preview; live webhook text/JSON preview (no send on preview).
- **Admin bar panel**: Always enabled when plugin is on (`notice_panel.enabled` on save); Admin bar tab is style/position/badge/colors only (no separate enable checkbox). Styles `link`, `bell_link`, `dropdown`, `dropdown_compact`; six position slots; badge caps and dot/pulse/none. Assets when `should_show_admin_bar_notice_panel()`. Settings preview via `initNoticePanelPreview()` (always renders mock). Dropdown JS via `enqueue_admin_bar_dropdown_script()`.
- **Settings UX**: Global **Enable** only on Dashboard Quick Status (not Settings save). No **Hidden notices in toolbar** option (removed). General tab: two-column **Display experience** + **Update & hidden notices** live previews; **Access** (ACL + user pickers); **Notices** tab (short Inbox/Hide/Default selects); **Admin bar** tab panel preview. Enqueues `list-tables`, `dashicons`, plugin-screen notice-hide inline CSS on plugin screens.
- **Plugin screens**: CSS + JS hide stray WP notices; settings preview mocks exempt; debug live-test queue cleared when visiting plugin screens.
- **Dashboard**: Quick Status enable toggle reloads the page after successful AJAX save (when site may edit settings).
- **AJAX**: rule mutations accept posted `context` (`site`|`network`); `verify_request()` checks `manage_network_options` for network context; settings toggles use `verify_site_settings_mutation()`.

## Admin Pages

Use `EDMINBOOST_ANM_Admin::PAGE_SLUG` — never hardcode slug strings.

| Page | Slug | Renderer |
|------|------|----------|
| Dashboard | `PAGE_SLUG` | `render_dashboard_page()` |
| Inbox | `PAGE_SLUG . '-inbox'` | `render_inbox_page()` |
| Rules | `PAGE_SLUG . '-rules'` | `render_rules_page()` |
| Settings | `PAGE_SLUG . '-settings'` | `render_settings_page()` |

**Network admin** (multisite; `EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY`):

| Page | Slug | Renderer |
|------|------|----------|
| Multisite policy | `EDMINBOOST_ANM_Multisite::NETWORK_PAGE_SLUG` | `render_network_policy_page()` |
| Network defaults | `NETWORK_SETTINGS_PAGE_SLUG` | `render_network_defaults_page()` |
| Network Auto-Rules | `NETWORK_PAGE_SLUG . '-rules'` | `render_network_rules_page()` |

## Notable AJAX actions

Registered in `EDMINBOOST_ANM_Ajax::register_hooks()` — all use `verify_request()` unless noted.

| Action | Purpose |
|--------|---------|
| `edminboost_anm_inbox_list` | Return sorted/filtered inbox `<tr>` HTML |
| `edminboost_anm_inbox_action` | Row/bulk inbox mutations (read, snooze/unsnooze toggle, archive, etc.) |
| `edminboost_anm_convert_wp_preset` | Create/undo rule from WP preset |
| `edminboost_anm_capture_notice` | Interceptor capture endpoint |
| `edminboost_anm_search_users` | User picker for hiding-notifications settings |
| `edminboost_anm_notice_hide` | Per-user or site-wide hide from inline UI or dropdown |
| `edminboost_anm_reset_hidden_notices` | Reset hidden-notice preferences (user or site) |
| `edminboost_anm_digest_preview` | Digest preview: to/from/subject, plain `body`, structured `content`, and `next_send` (unsaved `digest` fields including `send_weekday` sanitized server-side) |
| `edminboost_anm_webhook_preview` | Webhook alert text and JSON payload preview using latest inbox notice or sample data (unsaved `webhook[message]` sanitized server-side) |
| `edminboost_anm_seed_test_notices` / `edminboost_anm_trigger_test_notices` | `verify_debug_request()` |

## Extension Hooks

- `edminboost_anm_settings_defaults` — filter default settings
- `edminboost_anm_settings` — filter merged settings on read
- `edminboost_anm_sanitize_settings` — filter sanitized settings on save
- `edminboost_anm_notices_loaded` — after notice manager hooks register
- `edminboost_anm_upsell_preset_phrases` — filter upsell phrase preset list
- `edminboost_anm_wp_notice_presets` — filter WordPress core notice presets (Rules UI)
- `edminboost_anm_digest_email` — filter digest subject/body before send or preview
- `edminboost_anm_webhook_payload` — filter outbound webhook JSON payload

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

- Admin-only plugin — limited front-end hooks for admin bar notice panel when the toolbar is visible.
- Minimize diff scope; match existing file structure and style.
- Add `index.php` silence files when creating new directories.
- Update `readme.txt` changelog for user-facing changes; keep `.cursor/rules/` and `AGENTS.md` in sync when behavior or architecture changes.
- Update `uninstall.php` (and deactivator for cron) when adding stored options or schedules; multisite uninstall uses `EDMINBOOST_ANM_Multisite::uninstall_all_sites()`.
- New AJAX actions: register in `EDMINBOOST_ANM_Ajax`, use `verify_request()` (or `verify_debug_request()` for debug-only).
- Shared inbox row markup: update `edminboost-anm-inbox-table-rows.php` once for both full page and AJAX list responses.
