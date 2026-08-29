# Requirements: Moyasar Payments

## Overview
Take money through Moyasar in ViewClass, on the same Moyasar account that already
serves aafaq.xyz ("Rawani"), with every payment identifying which site it came from.

Decided with the owner on 2026-08-29: same account, invoices (a link that is sent)
rather than an in-app card form, and **configuration only for now** — what is being
charged for (platform subscription vs student fees) is not settled yet.

## User Stories

### US-001: Configured credentials
**As a** developer
**I want to** hold live and test Moyasar keys per environment
**So that** the same code runs against test money locally and real money on the server

**Acceptance Criteria:**
- WHEN `MOYASAR_MODE` is `test` THE SYSTEM SHALL use the test key pair
- WHEN `MOYASAR_MODE` is `live` THE SYSTEM SHALL use the live key pair
- WHEN a secret key is missing for the active mode THE SYSTEM SHALL fail with a clear message rather than attempt a call
- WHEN `php artisan moyasar:check` runs THE SYSTEM SHALL report whether the gateway accepts the configured key

### US-002: Payments name their origin  (NOT BUILT)
**As an** account owner reading the Moyasar dashboard
**I want** each payment tagged with the site that created it
**So that** aafaq money and ViewClass money are told apart in one account

**Acceptance Criteria:**
- WHEN this app creates an invoice or payment THE SYSTEM SHALL attach `metadata.site = config('moyasar.site')`

### US-003: Activation does not depend on the browser  (NOT BUILT)
**As a** paying customer
**I want** what I paid for to open even if my browser never returns to the site
**So that** a charged card always results in service

**Acceptance Criteria:**
- WHEN Moyasar posts a `payment_paid` webhook with the configured secret THE SYSTEM SHALL record the payment and grant what it paid for
- WHEN a request arrives at the webhook without the configured secret THE SYSTEM SHALL reject it with 401
- WHEN the same payment is delivered twice THE SYSTEM SHALL NOT grant it twice

## Non-Functional Requirements

### NFR-001: Security
- The secret key is server-side only and never rendered into a page
- A payment is granted only on the gateway's own confirmation, never on a query-string status
- The webhook's shared secret is never stored alongside the payment record

### NFR-002: Traceability
- Every payment event is recorded whether or not it matched a customer, so "he says he paid" is answerable from the database
