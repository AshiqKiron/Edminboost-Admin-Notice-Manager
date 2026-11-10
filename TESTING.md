# Testing — EdminBoost Admin Notice Manager

Automated quality gates cover PHP (WordPress test suite + PHPUnit) and browser E2E (Playwright). No test run can prove a plugin is “bug free,” but these layers catch regressions in sanitization, inbox logic, AJAX auth, and admin UI.

## Quick start (MAMP)

```bash
composer install
npm install
npx playwright install chromium

# One-time: MySQL test DB + WordPress test library (see tests/bin/install-wp-tests.sh)
npm run test:install-php

# PHP
npm run test:php

# E2E — copy credentials, then:
cp tests/e2e/env.example tests/e2e/.env
# Edit E2E_BASE_URL / E2E_WP_USER / E2E_WP_PASSWORD for your site
npm run test:e2e
```

## Quick start (Docker — @wordpress/env)

```bash
npm install
npm run env:start
# wp-env runs on http://localhost:8888 — user admin / password password
E2E_BASE_URL=http://localhost:8888 E2E_WP_PASSWORD=password npm run test:e2e
```

Activate the plugin in wp-env if needed:

```bash
npm run env:cli -- wp plugin activate edminboost-admin-notice-manager
```

## What each suite covers

| Suite | Command | Focus |
|-------|---------|--------|
| Unit | `npm run test:php:unit` | Settings sanitizers, rules/CSS matching, inbox add/query, debug helpers |
| Integration | `npm run test:php:integration` | Activation seeding, AJAX nonce/capability/toggle/inbox list |
| E2E smoke | `npm run test:e2e` | All admin pages load, assets enqueue, settings tabs |
| E2E AJAX | (in `ajax-contract.spec.ts`) | Nonce rejection, authenticated inbox list |
| E2E inbox | (in `inbox-flow.spec.ts`) | Enable plugin → capture notice → inbox UI |

### Debug-only dashboard tools

With `WP_DEBUG` true, Dashboard **Generate Test Notices** uses `edminboost_anm_seed_test_notices` / `edminboost_anm_trigger_test_notices`. E2E uses the production `edminboost_anm_capture_notice` path so tests work without debug mode.

## Environment variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `WP_TESTS_DIR` | `$TMPDIR/wordpress-tests-lib` | PHPUnit bootstrap |
| `WP_CORE_DIR` | `$TMPDIR/wordpress/` | WordPress core for tests |
| `E2E_BASE_URL` | `http://localhost:8888/wordpress` | Playwright base URL |
| `E2E_WP_USER` | `admin` | Login user |
| `E2E_WP_PASSWORD` | `password` | Login password |

## Reports

- Playwright HTML: `npm run test:e2e:report` (after a run)
- JSON results: `test-results/playwright-results.json`
- Summary script: `npm run qa:report` (PHPUnit + Playwright JSON if present)

## CI

GitHub Actions workflow `.github/workflows/tests.yml` runs PHPUnit and (optionally) Playwright on pushes/PRs when secrets and services are configured.

## Extending tests

1. **PHP** — Add `tests/unit/test-*.php` or `tests/integration/test-*.php`; extend `EDMINBOOST_ANM_Test_Case` or `WP_Ajax_UnitTestCase`.
2. **E2E** — Add specs under `tests/e2e/specs/`; reuse `tests/e2e/helpers/anm-api.ts` for nonce + AJAX.
3. After user-facing fixes, add a regression test that would have failed before the fix.

## Manual checklist (not automated)

- Multisite network policy / locked settings
- Weekly digest cron send (use staging mail)
- Outbound webhook to a request bin (opt-in)
- Admin bar notice panel on front-end with toolbar visible
- Pro-only code paths with `packages/pro` bootstrap
