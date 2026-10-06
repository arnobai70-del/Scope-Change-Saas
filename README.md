# Scope Change

Turn scope creep into approved change revenue before work starts.

Freelancers, agencies and consultants record the agreed scope of a project, turn each extra client request into a priced change request, and send a secure no-login approval link. The client approves, declines or asks a question on any device. Every decision is timestamped, revisions are immutable, the activity log is hash-chained, and a PDF **Proof Pack** captures the whole history.

> "Scope Change" is a working title. Change `APP_NAME` to rebrand.

## Features

- **Scope baselines**: deliverables, exclusions, assumptions and revision allowances, lockable and versioned with a SHA-256 content hash.
- **Change requests**: link to scope items, price in 25 currencies (integer minor units), timeline impact, payment condition (none, before start, before handoff, custom), templates.
- **Client approval page** (`/c/{publicId}/{token}`): branded, mobile-first, no login, `noindex`, rate-limited. Tokens are 48-character random strings stored only as SHA-256 hashes. Links expire, can be revoked, and stop working when a new revision is sent.
- **State machine** with enforced transitions: Draft, Sent, Viewed, Questioned, Approved, Declined, Payment pending, Payment marked sent, Payment confirmed, Ready to start, In progress, Completed, Proof packed, Expired, Revoked.
- **Idempotent decisions**: one decision per revision (database unique constraint plus a cache lock against double clicks).
- **Reminders** at 24 h, 72 h and 24 h before expiry, stopped by any decision, muted by a signed link, logged with a unique constraint so none is sent twice.
- **Proof Packs**: Dompdf PDF from locked snapshots, stored privately, checksummed, deterministic content hash.
- **Reports**: approved revenue by month, approval rate, median time to decision, revenue protected per currency.
- **Teams**: owner, admin and member roles, invitations, seat limits.
- **Billing**: Paddle Billing as merchant of record (overlay checkout, signed and idempotent webhooks, out-of-order protection, customer portal). Free, Solo, Pro and Agency plans with a 14-day Pro trial. Limits are enforced on the server.
- **Admin panel** (`/admin`, 2FA required): users, workspaces, suspensions, webhooks with retry, support tickets, feature flags, admin audit log.
- **Marketing site**: server-rendered pages, pricing, segment pages, guides, free change request generator, scope creep calculator, legal templates, sitemap and robots.
- **Privacy**: data export (JSON or CSV), deletion requests, IP retention pruning, email suppression list for bounces and complaints, first-party analytics with an allow-list of properties.

## Stack

| Layer           | Choice                                                                           |
| --------------- | -------------------------------------------------------------------------------- |
| Backend         | Laravel 13, PHP 8.4 (8.3 works)                                                  |
| Frontend        | Inertia v3, Vue 3, TypeScript, Tailwind CSS v4, shadcn-vue                       |
| Auth            | Laravel Fortify: registration, email verification, 2FA, password confirmation    |
| Database        | MySQL 8.4 in production, SQLite locally and in tests                             |
| Queue and cache | Redis in production, database driver locally                                     |
| PDF             | barryvdh/laravel-dompdf                                                          |
| Billing         | Paddle Billing (custom integration, no Cashier)                                  |
| Quality         | PHPUnit 12, PHPStan (Larastan) level 7, Pint, vite-plus lint and format, vue-tsc |

## Getting started

```bash
git clone https://github.com/arnobai70-del/Scope-Change-Saas.git
cd Scope-Change-Saas
composer setup            # install, .env, key, migrate, npm install, build
php artisan db:seed       # system templates + demo data (local only)
composer dev              # runs the dev processes (server, queue, Vite)
```

Open http://localhost:8000 and sign in with `demo@example.com` / `password`. The admin user is `admin@example.com` / `password` (enable 2FA under Settings, Security to open `/admin`). Demo users are only seeded when `APP_ENV=local`.

Scheduled jobs (expiry, reminders, trial notices, digest, privacy pruning) run through `php artisan schedule:work` locally or a cron entry in production.

## Quality checks

```bash
composer lint        # Pint (fix)
composer test        # Pint check, PHPStan level 7, PHPUnit
npm run check        # lint + format check
npm run types:check  # vue-tsc
npm run build
```

The suite has 116 tests covering tenant isolation, the state machine, the client portal (valid, wrong token, expired, revoked, superseded revision, double submit, honeypot), payment flow, money handling, Paddle webhook signatures, idempotency and stale events, reminders, Proof Packs, plan limits, the audit hash chain, admin access and every public page. It passes on SQLite and MySQL 8.

## Documentation

- [Architecture](docs/architecture.md)
- [Change request state machine](docs/state-machine.md)
- [Billing with Paddle](docs/billing.md)
- [Deployment](docs/deployment.md)
- [Incident response](docs/incident-response.md)

## License

Proprietary. All rights reserved.
