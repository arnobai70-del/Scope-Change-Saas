# Guide for AI coding agents

This guide is committed here because `AGENTS.md` and `CLAUDE.md` in the project root are generated locally by Laravel Boost and git-ignored.

Scope Change is a Laravel 13 + Inertia v3 + Vue 3 (TypeScript) SaaS. Read `README.md` and `docs/architecture.md` before changing code.

## What to work on

`docs/COMPLETION_CHECKLIST.md` is the source of truth for what is finished and what is left. Work through it in order:

1. Pick the first unchecked item that is not marked **(owner)**.
2. Build it with tests.
3. Run every quality gate below. All must pass.
4. Tick the item in the checklist in the same commit.

Never tick an unfinished item, never delete an item, and never skip, disable or weaken a test to get green.

## Quality gates

```bash
composer test        # Pint check, PHPStan level 7, PHPUnit
npm run check        # lint + format check
npm run types:check  # vue-tsc
npm run build
```

`composer ci:check` runs the same set that GitHub Actions runs.

## Rules that are easy to break

- Every tenant-owned query goes through `Model::forWorkspace($workspace)`; policies deny other workspaces' records as 404.
- Money is integer minor units plus an ISO 4217 code (`App\Support\Money`). Never use floats.
- Locked revisions, locked scope baselines, client decisions and audit events are immutable. A change after sending creates a new revision.
- Add product events to the audit trail through `AuditTrailService`; do not write audit rows by hand.
- Analytics properties must be on the allow-list; never record client text.
- Do not use `Route::redirect` (it breaks Wayfinder types); use `Illuminate\Routing\RedirectController` as in `routes/app.php`.
- Do not load fonts or scripts from a remote CDN; bundle assets through Vite.
- Client approval pages (`/c/*`) stay server-rendered Blade, `noindex`, with no app JavaScript.
- AI features may only produce drafts. Never auto-send or auto-price a change request.

## Demo logins (local only, after `php artisan db:seed`)

- `demo@example.com` / `password`
- `admin@example.com` / `password` (enable 2FA under Settings, Security to open `/admin`, or set `ADMIN_REQUIRES_2FA=false` in a local `.env`)
