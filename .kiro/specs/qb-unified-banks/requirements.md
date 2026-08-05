# Requirements: Unified Question Banks (بنك الأسئلة الموحّد)

## Overview
Today the three kinds of question bank — **عام/public** (platform-wide),
**خاص/private** (one school's own), and the **platform-owner's «منصة الأول» bank**
— have no single place to see them together, and the owner bank is
indistinguishable from a public bank in the schema. Schools take from the public
bank freely, but taking from the **owner** bank must be the owner's decision
(per-request yes/no). Schools add their own private questions; the owner must be
able to review those questions and lift the good ones up into the public or
owner bank.

Source: Trello card «بالنسبة ل بنك الأسئلة» (Gad, 2026-08-05).

## Actors
- **Owner / super-admin** — role slug `super-admin`. Owns the platform + the
  «منصة الأول» owner banks.
- **School admin** — role slug `school-admin`, pinned to one `school_id`.

## User Stories

### US-001: Unified three-tier view
**As a** super-admin
**I want to** see عام / خاص / منصة الأول banks in one screen with a tier per row
**So that** I stop hunting across scopes for banks.

- WHEN a super-admin opens the question-banks index THE SYSTEM SHALL show three
  tabs — عام (public), خاص لكل مدرسة (private), منصة الأول (owner) — each listing
  the banks of that tier with a visible tier badge.
- WHEN a school-admin opens the index THE SYSTEM SHALL show عام (public), خاص
  (their own private), and منصة الأول (owner banks, read-only with a request
  action) — never another school's private banks.

### US-002: Owner bank tier
**As a** super-admin
**I want to** mark a bank as an owner/«منصة الأول» bank
**So that** it is held apart from public banks and gated behind approval.

- WHEN a super-admin creates or edits a bank THE SYSTEM SHALL allow flagging it
  as an owner bank (`is_owner_bank`).
- THE SYSTEM SHALL treat owner banks as NOT freely copyable by schools (unlike
  public banks) and NOT list their questions to a school until access is granted.

### US-003: Free take from the public bank
**As a** school admin
**I want to** copy questions from a public bank into my school's bank
**So that** I reuse shared content without asking.

- WHEN a school-admin copies from a `visibility=public` bank THE SYSTEM SHALL
  perform the copy immediately, no approval required (existing `copyToMySchool`).

### US-004: Request + approval to take from the owner bank
**As a** school admin
**I want to** request access to an owner bank, pending the owner's yes/no
**So that** I can use the owner's questions only with permission.

- WHEN a school-admin requests an owner bank THE SYSTEM SHALL record a pending
  `question_bank_access_request` (bank, requesting school, requester).
- WHEN the owner approves a request THE SYSTEM SHALL let that school copy from
  that owner bank; WHEN the owner rejects it THE SYSTEM SHALL block the copy.
- WHILE a request is pending or rejected THE SYSTEM SHALL NOT allow the school to
  copy from that owner bank.
- THE SYSTEM SHALL notify the owner of a new request and the school of a decision.

### US-005: School adds its own private questions
**As a** school admin
**I want to** add questions to my school's private bank
**So that** I build my own content. *(Already exists — regression-guard only.)*

### US-006: Owner reviews school-added questions
**As a** super-admin
**I want to** see the questions a school added to its private banks in one place
**So that** I can judge them without switching the scope selector per school.

- WHEN a super-admin opens the owner review screen THE SYSTEM SHALL list private
  banks across all schools with their question counts, filterable by school, and
  drill into a bank's questions.

### US-007: Promote selected questions upward
**As a** super-admin
**I want to** copy chosen questions from a school's bank into a public bank or an
owner bank
**So that** good school content becomes shared or owner content.

- WHEN a super-admin selects questions in a private bank and picks a destination
  (a public bank OR an owner bank) THE SYSTEM SHALL copy those `bank_questions`
  (and any passage) into the destination as new rows, leaving the originals
  intact.

## Non-Functional Requirements

### NFR-001: Multi-tenant safety
- Every read/write SHALL be scoped: a school-admin can never see or copy another
  school's private bank; copy-from-owner is blocked without an approved request.

### NFR-002: Build on the live column
- Tier logic SHALL use the wired `visibility` column plus the new
  `is_owner_bank` flag; the dead duplicate `bank_type` column SHALL NOT be relied
  on for scoping.

### NFR-003: Additive, reversible schema
- New columns/tables SHALL be additive with a `down()`; no destructive change to
  existing bank data.
