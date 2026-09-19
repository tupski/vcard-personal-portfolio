# Admin browser regression

Playwright suite for the admin panel plus the public Phase 2 baseline.
Requires Node.js with the `playwright` package and a Chromium binary.

## Run

```bash
# 1. App + seeded database
php artisan migrate:fresh --seed
php artisan serve --port=8000

# 2. Install Playwright (once) — any recent Node project works
npm i -D playwright          # or: npm i -D playwright-core + CHROME_PATH

# 3. Run the suite
node tests/Browser/admin-regression.js
```

Environment overrides:

```text
BASE         app URL          (default http://127.0.0.1:8000)
CHROME_PATH  Chromium binary  (default: %LOCALAPPDATA%\ms-playwright\chromium-*)
```

The suite is event-driven: it synchronizes on the server responses of every
form submit (`page.waitForResponse`) and on Turbo's `dialog` lifecycle, so it
is deterministic — no arbitrary sleeps. 130 checks: login, dashboard metrics,
Turbo navigation over all 15 admin sections, history back/forward, active-nav
state, full CRUD round trip reflected on the public frontend through the
content layer, server-side validation with old input, visibility toggles,
delete confirmation dialog, FK-protected delete, settings sync, and the
public regression sweep (6 viewports × 5 routes: overflow, design tokens,
filter, modal).

Admin credentials come from the seeded `ADMIN_*` env values.
