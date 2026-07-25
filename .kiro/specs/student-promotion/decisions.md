# Confirmed Decisions — Student Promotion

Locked with the product owner (Farouk / Mostafa) on 2026-07-25 before implementation.
These override the "open questions" defaults noted in `design.md`.

1. **Capacity overflow ("زي نظام المدارس")** — apply the card's rule verbatim:
   only the **incoming cohort** is considered for overflow. When an incoming
   class-*k* group would push the destination over capacity, the students whose
   names sort **last alphabetically** move to class *k+1*; if no next class
   exists, none of them move and a `ClassCapacityExceeded` notification is sent
   to the school admin (مدير المدرسة). Students who stayed (failed) are never
   relocated.

2. **Destination classes must already exist** — promotion does NOT auto-create
   next-year classes. Preview blocks with a clear message when a target class
   number is missing; the admin creates/copies them (via the existing
   `migrateClasses`) first.

3. **Legacy backfill = guided screen** — a one-time review screen where the
   admin confirms `grade_number` (1..12) for legacy grades and `number` for
   legacy classes. No silent auto-mapping.

4. **Promotion runs per gender track** — boys grade 7 → boys grade 8, girls with
   girls. Sections/classes carry gender and the class-number matching respects it.

5. **Graduation (agent default, kept)** — on graduation keep the student's last
   `section_id` (still visible under their final grade), null only
   `class_room_id`, and set `users.graduated_at`. The lowest grade ends empty and
   receives nobody.

## Build sequencing

- Phases 1–4 (safe, non-destructive: grade naming, class numbering, pass/fail,
  graduated filter) build first.
- Phase 5 (⚠️ destructive engine) only after 5.1 batch tables + 5.2 password
  gate + tested planner exist. No school-wide student mutation ships without the
  rollback path.
- All work verified locally (Valet, seeded two-grade school) before any deploy.
