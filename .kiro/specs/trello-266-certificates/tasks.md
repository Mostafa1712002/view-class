# Tasks: Certificates System (Trello #266)

## Implemented
- [x] `certificate_templates` table (school_id nullable, name, type, orientation, background_path, text_color, name_color, body JSON, created_by, soft-deletes)
- [x] Additive columns on `certificates`: `template_id` (nullable FK), `share_token` (unique), `progress`
- [x] `CertificateTemplate` model + repository (contract + Eloquent impl + binding)
- [x] Template CRUD (create/edit/delete) with background upload (jpg/jpeg/png/webp <1.5MB, dimension warning non-breaking), colors, orientation, 5 body lines + insert-placeholder buttons
- [x] Issue certificates (single + bulk) from a template — synchronous PDF generation + storage, progress 0→100
- [x] PDF output via mPDF (Arabic RTL, XB Riyaz, branded المنصة الذهبية); dynamic fields rendered as real text over template background (pdftotext-extractable)
- [x] Preview screen (student, share link, copy, view, send) + send screen (channels listed)
- [x] Public tokenised share link (no auth, published only)
- [x] Permission gating via `permission:certificates.*` (migrated from `role:`); module routes file required from routes/web.php
- [x] scopedSchoolId fail-closed; super-admin null-school verified (template persists school_id=NULL)
- [x] Non-regression: legacy file-upload cert flow (create/store/publish/pdf) intact

## Deferred / Partial (reported)
- [ ] Async Job + live progress polling — done synchronously instead (0→100 inline)
- [ ] Multi-channel send (SMS/in-platform/email/WhatsApp) — stub screen only; reuses future messaging wiring
- [ ] تقدير / إشعار درجات grades-source integration — template TYPES exist; no grades pull ({grade} = classroom name)
- [ ] Full WYSIWYG editor for عام type — textarea body lines fallback
- [ ] Signature / logo / stamp upload+management — deferred
- [ ] copy_link as a separate route — copy is client-side under the preview gate
