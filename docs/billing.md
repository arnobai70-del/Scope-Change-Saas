# Billing with Paddle

Paddle Billing is the merchant of record: it charges customers, calculates VAT, GST and sales tax, and issues invoices. The app never handles card data.

## Setup

1. Create products and prices in Paddle (Solo, Pro, Agency, monthly and yearly; optional extra seat).
2. Set the price ids in `.env` (`PADDLE_PRICE_*`), plus `PADDLE_API_KEY`, `PADDLE_CLIENT_SIDE_TOKEN` and `PADDLE_SANDBOX`.
3. Create a notification destination pointing to `https://your-domain/webhooks/paddle` with the events `subscription.*`, `transaction.*` and `adjustment.*`. Put its secret in `PADDLE_WEBHOOK_SECRET`.

## Checkout

`POST /app/billing/checkout` (owner only) validates the plan and returns Paddle.js options. The browser loads Paddle.js and opens the overlay. `customData.workspace_id` links the subscription to the workspace.

## Webhooks

`PaddleWebhookController`:

1. Verifies `Paddle-Signature` (`ts=…;h1=…`, HMAC-SHA256 of `ts:body`, constant-time compare, 5-minute tolerance) before parsing anything.
2. Stores the event in `webhook_events` keyed by `(provider, external_id)`. A duplicate delivery hits the unique index and is acknowledged without reprocessing.
3. Queues `ProcessProviderWebhook`, which retries with backoff and marks the event processed or failed.

`PaddleWebhookHandler` ignores subscription updates older than the stored `provider_updated_at`, so out-of-order deliveries cannot roll state back. The workspace plan is the subscription plan while it is active or past due, and `free` otherwise.

Failed events are visible in **Admin, Webhooks** and can be retried.

## Plans and limits

Defined in `config/plans.php`. Limits (`null` means unlimited) and features are enforced by `UsageLimitService` and `PlanService`. New workspaces get a 14-day trial of `PLAN_TRIAL_PLAN` (Pro). Downgrades never delete data: existing records stay readable and exportable.
