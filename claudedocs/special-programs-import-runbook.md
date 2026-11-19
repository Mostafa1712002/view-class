# Runbook: importing the «الأول» bank (قدرات / تحصيلي / نافس) — Trello #368

Command: `php artisan special-programs:import-alawwal` (re-runnable, idempotent).
Code: `app/Modules/SpecialPrograms/Actions/Import/`, `app/Console/Commands/SpecialProgramsImportAlAwwal.php`. Design: D§4.

## 1. Build the slim extract (local, no student data leaves the machine)

Source snapshot `~/www/alawol/قاعدة البيانات/alawal-live-2026-11-19.sql.gz` is already loaded in local DB `alawal_local` (read-only use).

```bash
mysqldump -uroot -p123 alawal_local subjects skills classrooms grades school_types | gzip > sp-alawwal-extract.sql.gz.part1
mysqldump -uroot -p123 alawal_local questions   --where="exam_type IN ('qiyas','tahsili','nafis')" | gzip > sp-alawwal-extract.sql.gz.part2
mysqldump -uroot -p123 --single-transaction alawal_local classroom_question --where="question_id IN (SELECT id FROM questions WHERE exam_type IN ('qiyas','tahsili','nafis'))" | gzip > sp-alawwal-extract.sql.gz.part3
cat sp-alawwal-extract.sql.gz.part{1,2,3} > sp-alawwal-extract.sql.gz     # concatenated gzip members load fine
```

Image list and transfer (only referenced files, about 190 MB):

```bash
mysql -uroot -p123 -N alawal_local -e "SELECT question_image FROM questions WHERE exam_type IN ('qiyas','tahsili','nafis') AND question_image IS NOT NULL" > images.txt
rsync -a --files-from=images.txt ~/www/alawal/ rawaniedu:~/sp-import/
scp sp-alawwal-extract.sql.gz rawaniedu:~/
```

## 2. On the target box

```bash
mysql -e "CREATE DATABASE sp_alawwal_import CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
zcat ~/sp-alawwal-extract.sql.gz | mysql sp_alawwal_import
php artisan migrate --force            # Phase 1 sp_* tables + subject seed must exist
php artisan special-programs:import-alawwal --database=sp_alawwal_import --username=... --password=... \
    --images=$HOME/sp-import --dry-run
# compare with the expected totals below, then run for real (same command without --dry-run)
# re-run once: everything must say "unchanged" and images "same"
```

Options: `--program=qiyas` (repeatable), `--report=/path.json` (default `storage/logs/sp-import-<ts>.json`), `--host`, `--port`. Images land in `storage/app/public/special-programs/<program>/` (gitignored data; `php artisan storage:link` must exist for them to be served).

## 3. Expected totals (local run 2026-11-19, from `alawal_local`, 6 successive runs identical)

| program | source rows | items before dedupe | dedupe groups / rows merged | items after | passages / sub-questions | images copied | missing | orphan class links |
|---|---|---|---|---|---|---|---|---|
| qiyas | 3,571 | 3,630 | 183 / 223 | 3,407 | 58 / 117 | 1,907 | 0 | 0 |
| tahsili | 2,704 | 2,704 | 5 / 5 | 2,699 | 0 / 0 | 2,697 | 0 | 0 |
| nafis | 1,031 | 1,714 | 20 / 21 | 1,693 | 68 / 751 | 340 | 0 | 60 |

Totals: 7,799 `bank_questions`, 31,184 `question_answers`, 22 `sp_skills`, 126 passages, 871 `passage_questions`, 4,944 image files (199 MB), 9 owner banks. 0 decode failures, 0 unknown skills.

The dedupe numbers differ from the estimate in D§4.4 (≈327/149 qiyas, ≈53/26 nafis, 0 tahsili) because the key now includes the skill and the image bytes. The JSON report lists every group (`dedupe_group_list`: survivor = lowest source id, plus merged ids) so the client can veto.

Level histogram (items per ViewClass grade): 2:106, 3:714, 4:497, 5:549, 6:1139, 7:221, 8:589, 9:1382, 10:2, 12:2699 (an item with several levels counts once per level).

## 4. Verification SQL

```sql
SELECT s.program, COUNT(*) FROM sp_questions q JOIN sp_subjects s ON s.id=q.sp_subject_id GROUP BY 1;   -- 3407 / 2699 / 1693
SELECT COUNT(*) FROM bank_questions WHERE external_platform='al_awwal';                                 -- 7799
SELECT COUNT(*) FROM sp_questions q WHERE NOT EXISTS (SELECT 1 FROM sp_question_levels l WHERE l.sp_question_id=q.id);  -- 0
SELECT COUNT(*) FROM sp_questions WHERE sp_subject_id=7 AND sp_skill_id IS NULL;                         -- 0 (nafis reading has per-sub-question skills)
SELECT COUNT(*) FROM passages WHERE passage_code LIKE 'AW-%';                                            -- 126
```
Then open the Special Programs question bank screens: one question per program (image question, passage sub-question) renders its image and options.

## 5. Cleanup (target box)

```bash
mysql -e "DROP DATABASE sp_alawwal_import"
rm -rf ~/sp-import ~/sp-alawwal-extract.sql.gz
```

## Notes

- The source connection is built at runtime (`alawwal`), read-only, never through models. No change to `config/database.php`.
- Identity of an imported item: `bank_questions (question_bank_id, external_platform='al_awwal', external_id)`; `external_id` = `<source id>` or `<source id>:<sub_index>`. `metadata.sp_sig` holds the content signature used to detect "unchanged".
- `--dry-run` reads (source and target DB, image files) but writes nothing, including no image copies.
- One `activity_logs` row per real run.
