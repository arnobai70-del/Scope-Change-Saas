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

## PayPal Subscriptions (alternative provider)

Set `BILLING_PROVIDER=paypal` to send new checkouts to PayPal instead of Paddle. Existing Paddle subscriptions keep working; each subscription remembers its own provider.

### Setup

1. In [developer.paypal.com](https://developer.paypal.com), open Apps & Credentials, pick Sandbox or Live, and copy the app's Client ID and Secret into `PAYPAL_CLIENT_ID` and `PAYPAL_CLIENT_SECRET`. Set `PAYPAL_MODE` to `sandbox` or `live` to match. Never commit these keys.
2. Run `php artisan config:clear`, then `php artisan billing:paypal-setup --webhook-url=https://your-domain/webhooks/paypal`. It creates the product, one plan per paid plan and interval (prices from `config/plans.php`), registers the webhook, and saves `BILLING_PROVIDER`, `PAYPAL_MODE`, `PAYPAL_PLAN_*` and `PAYPAL_WEBHOOK_ID` to `.env`. Leave out `--webhook-url` when the site has no stable public address yet. If the plans already exist, `php artisan billing:paypal-env` reads them from PayPal and writes the ids to `.env` without creating new ones.
3. Run `php artisan config:clear` again.

### Flow

- Choosing a plan opens a "Pay by card" dialog on the billing page. It loads the PayPal JS SDK with the public client id and renders PayPal's card button, which opens PayPal's own card fields on the same page (it falls back to the regular PayPal buttons where cards are not offered). Card numbers go to PayPal only; this app never receives them.
- The SDK calls `POST /app/billing/checkout`, which creates the subscription on PayPal with `custom_id` set to the workspace id and returns its id. After the card is approved, the page calls `POST /app/billing/paypal/confirm`, which reads the subscription from the PayPal API and applies it only if `custom_id` matches the signed-in owner's workspace.
- `/app/billing/paypal/return` does the same for the approval page fallback.
- `/webhooks/paypal` asks PayPal to verify every delivery (`/v1/notifications/verify-webhook-signature` with `PAYPAL_WEBHOOK_ID`), stores it once in `webhook_events`, and queues it. Subscription events re-read the subscription from the API, so delivery order does not matter. `PAYMENT.SALE.COMPLETED` records the payment in `transactions`.
- Cancelling calls PayPal's cancel endpoint. PayPal stops billing at once, so the workspace keeps its plan until the end of the paid period (`cancels_at`) and then drops to Free. A cancelled PayPal subscription cannot be resumed; the customer subscribes again after it ends.
- "Manage on PayPal" opens the customer's PayPal automatic payments page.

Without `PAYPAL_WEBHOOK_ID`, checkouts still activate through the return page, but renewals, failed payments and cancellations made on PayPal are not received.

PayPal is not a merchant of record: you are responsible for sales tax and VAT on these payments.
