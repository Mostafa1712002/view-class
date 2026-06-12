# Design: Evaluation Engine v2

## Guiding principle — additive, non-breaking, sliced
The Sprint-8 engine is deployed + QA-verified. v2 evolves it; it does NOT rebuild. Every schema change
is an additive migration with safe defaults so existing evaluations keep scoring unchanged. The
high-blast-radius change (shared evaluation, US-202) is gated by a per-form flag and introduced
alongside — not in place of — the legacy per-evaluator path until parity is verified.

## Phasing (low-risk → core)
**Phase A — additive item config (#201, parts of #208/#210/#209)** — pure additive columns + UI.
**Phase B — evidence approval (#204)** — additive evidence.status + approval UI + scoring gate.
**Phase C — educational-outcome config (#205)** — settings + calc-method resolver.
**Phase D — granular permissions + audit (#208/#210, #209)** — permission catalog + AuditTrail expansion.
**Phase E — shared evaluation + per-item state (#202, #203)** — the core model evolution, form-flag gated.
**Phase F — screens (#206, #207) + Noor model (#211/#212)** — surface the new model in add/GM screens.
**Phase G — percentage labelling pass (#200)** — re-label UI; verify two-level display%/weight everywhere.

## Schema changes (all additive)
```sql
-- Phase A: evaluation_items
ALTER TABLE evaluation_items ADD responsible_role        VARCHAR(40) NULL;   -- school-admin|supervisor|complex-manager|general-manager|auto|...
ALTER TABLE evaluation_items ADD item_type              VARCHAR(20) NOT NULL DEFAULT 'manual'; -- manual|auto|evidence_only|mixed
ALTER TABLE evaluation_items ADD calc_method            VARCHAR(20) NOT NULL DEFAULT 'manual'; -- manual|auto_platform|after_evidence|external
ALTER TABLE evaluation_items ADD evidence_needs_approval TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE evaluation_items ADD editable_after_review   TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE evaluation_items ADD editable_after_approval TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE evaluation_items ADD min_percentage          DECIMAL(5,2) NULL;
ALTER TABLE evaluation_items ADD internal_notes          TEXT NULL;
-- display_percentage is conceptual: evaluator always scores out of 100; `weight` is the share in final.
-- No new column needed for display% (responses already hold the 0-100 value); weight stays the share.

-- Phase B: evaluation_evidences
ALTER TABLE evaluation_evidences ADD status       VARCHAR(20) NOT NULL DEFAULT 'approved'; -- uploaded|pending_approval|approved|rejected|needs_edit  (default 'approved' keeps legacy counted)
ALTER TABLE evaluation_evidences ADD reviewed_by  BIGINT UNSIGNED NULL;
ALTER TABLE evaluation_evidences ADD reviewed_at  TIMESTAMP NULL;
ALTER TABLE evaluation_evidences ADD review_note  TEXT NULL;

-- Phase E: per-item responses carry responsible + state (shared-evaluation core)
ALTER TABLE evaluation_responses ADD responsible_role VARCHAR(40) NULL;  -- denormalized from item at fill time
ALTER TABLE evaluation_responses ADD filled_by   BIGINT UNSIGNED NULL;
ALTER TABLE evaluation_responses ADD status      VARCHAR(20) NOT NULL DEFAULT 'draft'; -- draft|completed|pending_review|approved|rejected
ALTER TABLE evaluation_responses ADD submitted_at TIMESTAMP NULL;
ALTER TABLE evaluation_responses ADD approved_by BIGINT UNSIGNED NULL;
ALTER TABLE evaluation_responses ADD approved_at TIMESTAMP NULL;
ALTER TABLE evaluation_responses ADD reject_reason TEXT NULL;
ALTER TABLE evaluation_forms ADD shared_mode TINYINT(1) NOT NULL DEFAULT 0; -- 0 = legacy per-evaluator; 1 = one shared evaluation
```

## Shared-evaluation model (Phase E, form-flag gated)
- `shared_mode=0` (default, legacy): unchanged — one `evaluations` row per (form,evaluator,subject); ReportAggregator averages. Existing forms keep working.
- `shared_mode=1`: ONE `evaluations` row per (form, subject). Items carry `responsible_role`. Each evaluator fills only responses for items whose `responsible_role` matches their role (or items individually assigned). Per-item state lives on `evaluation_responses.status`. Final % computed from all item responses once each item reaches `approved`/`completed`. The existing scorers operate unchanged on the single response set (they already read all responses for an evaluation).
- Execution screen (`execute/show`) filters items by the current user's responsible_role unless they hold `view-all-items`.

## Scoring (US-200, #200) — refine, don't rewrite
- Each item: evaluator inputs a 0–100 percentage (already the case for rating/rubric→percentage). Item `earned = display% × (weight/Σweights)`.
- Evidence gate (#204): if `item.evidence_needs_approval` and its evidence isn't `approved`, the item contributes 0 (and is flagged "uncomputed") until approved, then recompute.
- Educational-outcome item (#205): `calc_method=auto_platform/external`; value resolved by `EducationalOutcomeResolver` using the configured averaging method + source; re-runnable ("recompute outcome").

## Permissions (US-208/210) & Audit (US-209)
- Add a permission catalog (constants) for the granular list; gate Actions/controllers on them (fall back to existing role gates so nothing breaks before roles are mapped).
- Expand `Services/AuditTrail` to record old/new value + reason + IP for each listed operation; surface in the existing audit screen with new operation types.

## Settings home (#205)
- Reuse the school/company settings mechanism; add `eval.outcome_method` (+ scope level) and `eval.outcome_source` keys, read via a resolver with company→complex→school precedence.

## Technology
- Laravel 12 / PHP 8.4, existing module `app/Modules/Evaluation`, repository+action+scoring patterns already in place. No new libraries.
