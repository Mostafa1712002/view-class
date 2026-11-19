# Tasks: Moyasar Payments

## Phase 1: Configuration

### Task 1.1: Credentials and environment
- [x] `config/moyasar.php` with test/live key pairs, mode, site tag, currency, webhook secret
- [x] `.env.example` entries
- [x] Test keys in local `.env`
- [x] `php artisan moyasar:check` command
- [x] Verified against the gateway locally (HTTP 200, test account reachable)

**Outcome:** ✅ Keys configurable per environment and provably working
**Dependencies:** None

---

## Phase 2: Charging  (DELIVERED by `.kiro/specs/rawani-hub-packages` Phase 3, 2026-11-19: Rawani packages on rawaniedu.com)

### Task 2.1: Invoices
- [x] Decide the billed subject: Rawani hub packages (rawaniedu.com only); school subscriptions / student fees still undecided
- [x] `hub_payments` + `hub_payment_events` tables
- [x] Create invoice via Moyasar Invoices API with `metadata.site` (`StartCheckout`, `MoyasarGateway`)
- [x] Send the invoice link to the payer (self-checkout redirect to the hosted invoice; an admin «send link» is not built)

### Task 2.2: Webhook
- [x] Endpoint verifying `secret_token` with `hash_equals` (`POST /api/hub/moyasar/webhook`)
- [x] Record every event; grant idempotently (`ApplyPaidPayment`, plus `hub:reconcile-payments`)
- [ ] Register the webhook in the dashboard and confirm with a test payment (rollout step 6.4 of rawani-hub-packages)

### Task 2.3: Admin view
- [x] Screen listing payments, flagging any paid one that matched no customer (`admin/hub/payments`)

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Configuration | 1 | 1 | Done |
| 2. Charging | 3 | 3 | Delivered by rawani-hub-packages (webhook registration at rollout) |
| **Total** | **4** | **4** | **100% (code)** |
