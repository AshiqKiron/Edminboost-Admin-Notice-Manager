# Monorepo layout

Source is split into shared **core** and **edition overlays** (Free / Pro). Release builds assemble a flat WordPress plugin tree and zip each edition.

## Directories

| Path | Purpose |
|------|---------|
| `packages/core/` | Shared PHP, admin assets, languages, `boot.php`, `load.php`, `uninstall.php` |
| `packages/free/` | Free bootstrap + `readme.txt` |
| `packages/pro/` | Pro bootstrap + `readme.txt` |
| `build/` | `build.mjs` + `config.json` |
| `dist/` | Build output (gitignored) |

## Local development (MAMP / wp-content)

Activate the repo folder as today: root `edminboost-admin-notice-manager.php` loads `packages/core` via `core_dir` / `core_url` overrides in `boot.php`.

## Release zips

```bash
npm run build        # Free + Pro folders + zips
npm run build:free   # Free only
npm run build:pro    # Pro only
```

Output:

- `dist/edminboost-admin-notice-manager/` — Free plugin folder
- `dist/edminboost-admin-notice-manager-pro/` — Pro plugin folder
- `dist/zips/edminboost-admin-notice-manager-{version}.zip`
- `dist/zips/edminboost-admin-notice-manager-pro-{version}.zip`

Each assembled plugin contains core files at the plugin root (same layout WordPress expects).

## Edition constants

- Free: `EDMINBOOST_ANM_IS_PRO` false, slug `edminboost-admin-notice-manager`
- Pro: `EDMINBOOST_ANM_IS_PRO` true, slug `edminboost-admin-notice-manager-pro`

Use `edminboost_anm_is_pro()` when gating Pro-only behavior. To omit Pro-only files from the Free zip later, add relative paths to `excludeFromCore` for the `free` edition in `build/config.json` and guard runtime with `edminboost_anm_is_pro()` / `class_exists()` checks.
