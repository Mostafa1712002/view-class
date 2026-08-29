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

## Phase 2: Charging  (BLOCKED — owner to decide what is charged for)

### Task 2.1: Invoices
- [ ] Decide the billed subject: school platform subscription, or student fees
- [ ] `invoices` + `payment_events` tables
- [ ] Create invoice via Moyasar Invoices API with `metadata.site`
- [ ] Send the invoice link to the payer

### Task 2.2: Webhook
- [ ] Endpoint verifying `secret_token` with `hash_equals`
- [ ] Record every event; grant idempotently
- [ ] Register the webhook in the dashboard and confirm with a test payment

### Task 2.3: Admin view
- [ ] Screen listing payments, flagging any paid one that matched no customer

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Configuration | 1 | 1 | Done |
| 2. Charging | 3 | 0 | Blocked on scope |
| **Total** | **4** | **1** | **25%** |
