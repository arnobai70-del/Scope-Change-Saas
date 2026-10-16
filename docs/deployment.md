# Deployment

## Requirements

- PHP 8.4 with `pdo_mysql`, `intl`, `mbstring`, `gd` (Dompdf), `redis` (or predis)
- MySQL 8.4, Redis 7
- Node 22 for building assets
- A transactional email provider (Postmark, SES, Resend…) with bounce and complaint webhooks

## Environment

Copy `.env.example` and set at least:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
DB_CONNECTION=mysql
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SECURITY_HSTS=true
MAIL_MAILER=...
PADDLE_* (see billing.md)
MAIL_WEBHOOK_SECRET=...
```

## Release steps

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=SystemTemplateSeeder --force   # idempotent
php artisan optimize
php artisan queue:restart
```

## Processes

- Web: PHP-FPM behind Nginx or Caddy, serving `public/`.
- Queue: `php artisan queue:work redis --tries=3 --backoff=10` under Supervisor or systemd (or Laravel Horizon).
- Scheduler: `* * * * * php /path/artisan schedule:run` (expiry every 5 minutes, reminders every 15 minutes, trial notices daily, weekly digest, daily privacy pruning).

## Share from your own computer (demo)

To open the app from any network without a server, run a Cloudflare quick tunnel next to `php artisan serve`:

```bash
# once (Windows): winget install --id Cloudflare.cloudflared
# in .env: TRUSTED_PROXIES=127.0.0.1, then php artisan config:clear
php artisan serve
cloudflared tunnel --url http://127.0.0.1:8000
```

Cloudflared prints an `https://….trycloudflare.com` address that works from any device until you stop it. The address changes on every run and the computer must stay on, so use a real server for production.

## Behind a proxy or load balancer

If TLS terminates at a load balancer, trust it with `TRUSTED_PROXIES=10.0.0.0/8` (comma-separated IPs or CIDRs) so that client IPs, HTTPS URLs and signed links are correct.

## Storage

Proof Packs and data exports are written to the private `local` disk (`storage/app/private`). Use a persistent volume or switch the disk to S3 with private ACLs. Never expose this directory publicly. Logos use the `public` disk; run `php artisan storage:link`.

## Backups

- Nightly encrypted MySQL dumps kept for 30 days, plus point-in-time binlogs if available.
- Back up the private storage disk with the same schedule.
- Test a restore every quarter.

## Health

Create the first platform admin on the server with `php artisan app:make-admin you@example.com` (it creates the user, or promotes an existing one). The admin must enable two-factor authentication before `/admin` opens.

`/up` returns 200 when the app boots. Monitor queue depth, failed jobs and failed webhooks from the admin dashboard.
