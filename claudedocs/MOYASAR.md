# Moyasar payments — setup and runbook

ViewClass takes card payments through **Moyasar**, on the same account that already
serves aafaq.xyz. One account, two products, so every payment must say which site
it came from.

- Dashboard: <https://dashboard.moyasar.com>
- Entity: **Rawani** — `d64efb6a-4a9a-40c0-8bc8-5243a887b3df`
- Owner login: khaled Eldayel (`min722774@gmail.com`) — ask the owner, it is not written down here
- Sign-in needs a 2FA code emailed to that address

## Where the keys live

**Never in the repository.** `.env` is gitignored; that is the only place a secret
key belongs, plus the server's own `.env`.

| Variable | Environment | Value |
|----------|-------------|-------|
| `MOYASAR_TEST_PUBLISHABLE_KEY` | test | `pk_test_RtWv2LWpgZkdrMuSChYwCujeKorqAsEALnYSiDdo` |
| `MOYASAR_TEST_SECRET_KEY` | test | `sk_test_6Gyj…` — dashboard → Settings → API Keys, Test |
| `MOYASAR_LIVE_PUBLISHABLE_KEY` | live | `pk_live_jNanz4e4zdpmU5rbxeunGEz6Q2EVnTdnUdeEMKJp` |
| `MOYASAR_LIVE_SECRET_KEY` | live | `sk_live_8qz5…` — dashboard → Settings → API Keys, Live |
| `MOYASAR_WEBHOOK_SECRET` | both | generated per install, also entered in dashboard → Settings → Webhooks |

The publishable keys are printed above because they are public by design — they ship
inside the payment page's HTML. **The secret keys are deliberately not written here**:
this file is committed, and a secret key can charge, refund and read every payment on
the account. Both are already set in the local `.env` and are read from the dashboard
when a new machine or server needs them.

`MOYASAR_MODE` selects which pair is used. Keep it `test` everywhere except the
production server.

## Checking the setup

```bash
php artisan moyasar:check              # uses MOYASAR_MODE
php artisan moyasar:check --mode=live  # checks the other pair without switching
```

It prints which keys are set and asks the gateway whether it accepts the secret.
Verified 2026-08-29: test → HTTP 200 (18 payments), live → HTTP 200 (411 payments).

## Telling the two sites apart

`config('moyasar.site')` (`MOYASAR_SITE`, default `viewclass`) goes into every
payment's `metadata.site`. In the dashboard, open a payment and its metadata names
the origin. aafaq's payments carry the buyer's email and app the same way.

## Rules this integration follows

These come from an outage on aafaq.xyz found on 2026-08-29, where web card payments
had been taking money without opening accounts **since February 2025** — 31 customers.

1. **A browser redirect is not a payment notification.** The buyer's browser may never
   come back. Moyasar's webhook, posted server to server, is what grants access.
2. **Never trust a status in the query string.** `?status=paid` in the address bar
   must unlock nothing. Grant only on the webhook's authenticated event, or on a
   `GET /v1/payments/{id}` confirmation made with the secret key.
3. **Never rely on your own query parameters surviving.** Moyasar rebuilds the callback
   URL and drops extras — aafaq lost `useremail` that way. Put the buyer's identity in
   `metadata`, which comes back intact.
4. **Record every payment event**, matched or not. A paid payment that matched no
   customer is the case that silently strands people; it needs to be visible.
5. **Applying a payment is idempotent.** The webhook and the landing page can both
   reach the same payment; the second one must change nothing.
6. **Never store the webhook's shared secret with the event.** Strip it before saving.
7. **Keep logs out of the web root**, or deny them in the server config — payment logs
   carry customer emails.

## Why there is no SDK

`moyasar/moyasar` and `moyasar/moyasar-php` are both marked abandoned on Packagist,
last released March 2022, before Laravel 9. The API is REST with HTTP Basic auth:

```php
Http::withBasicAuth(config('moyasar.keys.'.config('moyasar.mode').'.secret'), '')
    ->get(config('moyasar.api_url').'/payments', ['per' => 1]);
```

## What is not built yet

Only configuration exists. What is charged for — a school's platform subscription or a
student's fees — has not been decided, and it determines who the customer is, what the
invoice says and what a successful payment grants.

Remaining work is tracked in `.kiro/specs/moyasar-payments/tasks.md`: invoices via
`POST /v1/invoices`, the webhook endpoint, and an admin payments screen.

The working reference is aafaq.xyz: `moyasar-webhook.php`,
`includes/payment_activation.php`, the `payment_events` table and `payments.php`.
