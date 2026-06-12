# Design: Percentage-based Evaluation Scoring (Trello #200)

## Context — what already exists (verified 2026-06-13)
The eval engine is **already weighted-percentage based**. Do NOT rebuild it.
- `evaluation_items.weight decimal(6,2)` = item weight; `max_score`, `min_percentage` also present.
- Scoring lives in `app/Modules/Evaluation/Scoring/` (open/closed strategy pattern):
  - `RubricScorer`, `RatingScaleScorer`, `ChecklistScorer` — each: `earned = weight × fraction`, `max = Σweights` (=100 for a valid form).
  - `ScoreResult::make($total,$max,$breakdown)` → `percentage = total/max×100`, rounds to 2dp, derives Arabic grade label. Per-item `breakdown` already carries `{item_id,item_name,weight,earned,max,...}`.
  - `ScoringStrategyFactory::for(FormType)` resolves the scorer.
- `evaluations` already stores `total_score,max_score,percentage,grade_label,score_breakdown(json)`.
- `evaluation_responses.score decimal(8,2)` per item; plus `item_status`, approval fields, `responsible_role`.
- `FormType` enum = {rubric, rating_scale, checklist}. NO percentage type yet.
- `CalcMethod` enum = {manual, auto_platform, after_evidence, external}.

## The delta #200 actually requires
A **direct percentage-entry mode**: the evaluator types a 0–100 % per item; the item's
admin-set `weight` is its share of the final; `calculated_item = (entered%/100) × weight`;
`final = Σ calculated_item`. Since Σweights = 100, final is itself a 0–100 %.

This is a NEW `FormType::Percentage`, implemented additively. **The existing 3 scorers
and their forms are untouched → full scoring parity for all deployed evaluations.**

## Tasks

### 1. Enum + scorer (core, low-risk, additive)
- `FormType::Percentage = 'percentage'` (+ `label()` case → new lang key `evaluation.types.percentage` = "نسبة مئوية").
- New `app/Modules/Evaluation/Scoring/PercentageScorer.php` implementing `ScoringStrategy`,
  mirroring `RubricScorer` structure but reading the evaluator's per-item percentage from
  `evaluation_responses.score` (0–100):
  ```
  foreach active item:
      weight   = item.weight
      max     += weight
      pct      = clamp(response.score ?? 0, 0, 100)     // evaluator's entered %
      earned   = round(weight * (pct / 100), 2)          // calculated_item_percentage
      total   += earned
      breakdown[] = {item_id,item_name,weight,earned,max:weight,entered_percentage:pct}
  return ScoreResult::make(total, max, breakdown)
  ```
- Add `FormType::Percentage => new PercentageScorer()` case to `ScoringStrategyFactory`.

### 2. Form-builder: allow creating a Percentage form
- `EvaluationFormRequest` / form-create view: include `percentage` in the type options.
- A percentage form's **items need only name + weight** (no levels/indicators). The item
  form (`EvaluationItemController` + view) already collects `weight`; for percentage forms,
  hide/skip the indicator/level UI (branch on form type).

### 3. Weight-sum = 100 validation (applies to weighted form types)
- On form publish/approval (find the publish/activate action — likely `SaveEvaluationForm`
  or `EvaluationApprovalController`), compute `Σ active item weights`. If ≠ 100:
  - WARN in the builder UI (non-blocking display: "مجموع الأوزان = X% (يجب أن يكون 100%)").
  - BLOCK publish/approve unless the actor has a special permission
    (`evaluation.publish_incomplete_weights` — reuse existing permission infra; if none,
    gate on super-admin). Card rule: "لا يسمح باعتماد نموذج … أوزانه غير مكتمل إلا بصلاحية خاصة".
- Item percentage input validation: evaluator response `score` for a percentage item must be
  `numeric, between:0,100` (in the response store request/action).

### 4. Evaluator UI (percentage entry)
- In the fill-evaluation view (`MyEvaluationsController` show + its blade), branch on form
  type: for `percentage`, render a single `number` input per item (`min=0 max=100 step=0.01`)
  bound to that item's response `score`, with the item framed as "من 100%". No level pickers.
- Live helper text per item: "الوزن داخل التقييم: {weight}% — المحتسب: {entered×weight/100}%".

### 5. Results display (per-item calculation detail)
- Wherever results render the breakdown (result view + GM/job-perf screens #206/#207),
  for percentage forms show columns: البند | نسبة المقيّم (entered%) | وزن البند | النسبة المحتسبة (earned).
  The breakdown json already carries these; just surface them. Final = `evaluation.percentage`.

## Rounding & rules (card)
- Round percentages to 2 decimals (ScoreResult already does).
- Item % bounded 0–100 (validation).
- Keep legacy numeric fields (`total_score`, `max_score`) populated for back-compat, but the
  UI/headline is the **percentage**.

## Non-negotiable parity guarantee
Do NOT modify `RubricScorer`, `RatingScaleScorer`, `ChecklistScorer`, or `ScoreResult`.
Percentage is a new branch only. Snapshot immutability still holds (scorers read the frozen
snapshot payload). Test: an existing rating/rubric evaluation must produce the identical
`total_score/percentage/grade_label/score_breakdown` after this change.

## Verification
- Unit-style: feed a percentage snapshot + responses to `PercentageScorer`, assert
  `earned = weight×pct/100` and final = Σ.
- E2E (Playwright, live + local): admin builds a percentage form (2 items, weights 60/40),
  evaluator enters 80% / 50% → final = 0.8×60 + 0.5×40 = 48 + 20 = 68%. Result shows 68% +
  per-item detail. Confirm a rubric form built earlier still scores unchanged.
- Weight-sum guard: a form with weights 60/30 (=90) warns and blocks approve for a normal admin.
