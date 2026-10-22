# Design

- `schools`: `manager_user_id`, `agent_user_id` (nullable unsigned bigint + index, no FK — users.id type differs between installs; users are soft-deleted), `stamp` (path on public disk, `schools/stamps/`).
- `School::manager()` / `agent()` belongsTo User (withTrashed).
- Candidates (`SchoolController::staffCandidates`): manager = job title slug `school_manager` OR role `school-admin`; agent = job title slug `vice`. Across all schools (a new school has no users yet), labelled `name — school`; the currently linked user is always kept. Validation = `Rule::in(candidates)`. Route is super-admin only.
- Certificates: `RenderCertificatePdfAction` loads the school once with manager/agent; `stamp_url` falls back to school stamp; footer prints manager (when no signer_name) and agent; `{manager}`/`{agent}` placeholders in body lines.
- RTL arrow: one rule in `layouts/app.blade.php` — `html[dir=rtl] .select2-container--bootstrap4 … .select2-selection__arrow {left:3px; right:auto}` (theme had no RTL rule). Native selects already render RTL correctly.
- External «الأول بلس» self-learning certificates: no API for schools exists in this repo; if that platform needs these fields, an endpoint is a follow-up.
