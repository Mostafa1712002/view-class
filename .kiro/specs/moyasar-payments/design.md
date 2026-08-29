# Design: Moyasar Payments

## Why no package
`moyasar/moyasar` and `moyasar/moyasar-php` are both marked abandoned on Packagist
(last release March 2022, before Laravel 9). The API is REST with HTTP Basic auth,
which `Http::withBasicAuth($secret, '')` covers, so nothing is gained by adopting an
unmaintained dependency.

## Configuration
`config/moyasar.php`, driven by env:

| key | meaning |
|-----|---------|
| `mode` | `test` or `live` — selects the key pair |
| `site` | tag written into every payment's metadata (`viewclass`) |
| `api_url` | `https://api.moyasar.com/v1` |
| `currency` | `SAR` |
| `keys.test` / `keys.live` | publishable + secret per environment |
| `webhook_secret` | token Moyasar echoes back with each event |

Local `.env` holds the test pair only. Live keys belong on the server.

## Verifying credentials
`php artisan moyasar:check [--mode=test|live]` prints which keys are set and calls
`GET /payments?per=1`. A working key answers 200 with the account's payment count.

## Still to design (blocked on the owner deciding what is charged for)
- What an invoice is raised against: a school's platform subscription, or a student's fees
- Invoice creation via `POST /v1/invoices` with `metadata.site` and the payer's identity
- `invoices`/`payment_events` tables, and what a paid invoice grants
- Webhook endpoint + registration in the dashboard (aafaq's `moyasar-webhook.php` is the working reference: verify the shared token, record the event, apply idempotently)

## Reference implementation
aafaq.xyz already runs this pattern end to end: `moyasar-webhook.php`,
`includes/payment_activation.php`, `payment_events` table, admin payments screen.
Its lesson: a browser redirect is not a payment notification. See the memory note
`project_aafaq_moyasar_payments`.
