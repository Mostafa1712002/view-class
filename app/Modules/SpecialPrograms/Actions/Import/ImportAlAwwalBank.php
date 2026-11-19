<?php

namespace App\Modules\SpecialPrograms\Actions\Import;

use App\Models\ActivityLog;
use App\Models\BankQuestion;
use App\Models\QuestionBank;
use App\Modules\QuestionBankCore\Actions\CreateQuestion;
use App\Modules\QuestionBankCore\Actions\MapAnswerData;
use App\Modules\QuestionBankCore\Models\Passage;
use App\Modules\SpecialPrograms\Models\SpQuestion;
use App\Modules\SpecialPrograms\Models\SpSkill;
use App\Modules\SpecialPrograms\Models\SpSubject;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * D§4: imports the «الأول» qiyas / tahsili / nafis bank into bank_questions + sp_*.
 * Pass 1 (always) reads, normalises, dedupes and checks images in memory.
 * Pass 2 (real run only) writes, one transaction per surviving source row.
 */
final class ImportAlAwwalBank
{
    public function __construct(
        private AlAwwalRowNormalizer $normalizer,
        private AlAwwalLevelMap $levelMap,
        private MapAnswerData $mapAnswers,
        private CreateQuestion $createQuestion,
    ) {}

    /**
     * @param  list<string>  $programs
     * @return array<string,array> report per program
     */
    public function execute(ConnectionInterface $src, string $imagesDir, array $programs, bool $dryRun, ?callable $log = null): array
    {
        $log ??= fn () => null;
        $report = [];

        foreach ($programs as $program) {
            $subjects = SpSubject::where('program', $program)->orderBy('sort_order')->get()->keyBy('source_subject_id');
            $plan = $this->plan($src, $program, $subjects->all(), $imagesDir, $log);

            if (! $dryRun) {
                $this->write($src, $program, $subjects->all(), $plan, $imagesDir, $log);
            } else {
                $this->classifyDryRun($program, $subjects->all(), $plan);
            }

            $report[$program] = $this->summarise($plan);
        }

        if (! $dryRun) {
            ActivityLog::log('import', 'استيراد بنك أسئلة «الأول» (قدرات/تحصيلي/نافس): '.implode('، ', $programs), null, null, $report);
        }

        return $report;
    }

    // ───────────────────────────── pass 1 ─────────────────────────────

    private function plan(ConnectionInterface $src, string $program, array $subjects, string $imagesDir, callable $log): array
    {
        $srcSubjectIds = array_keys($subjects);
        $skillRows = $src->table('skills')->whereIn('subject_id', $srcSubjectIds)->orderBy('id')->get();
        $knownSkills = $skillRows->pluck('id')->map(fn ($i) => (int) $i)->all();

        $plan = [
            'skills' => $skillRows,
            'known_skills' => array_flip($knownSkills),
            'rows' => [],          // source id => plan row (successfully decoded)
            'stats' => [
                'rows_read' => 0, 'items_before_dedupe' => 0, 'passages_before_dedupe' => 0,
                'decode_failures' => 0, 'unknown_skill' => 0, 'orphan_links' => 0, 'unmapped_levels' => 0,
                'images_missing' => 0, 'image_conflicts' => 0,
            ],
            'errors' => [], 'missing_images' => [], 'dedupe_groups' => [],
        ];
        $targets = []; // image target path => md5 (conflict detection)

        $src->table('questions')->where('exam_type', $program)->whereIn('subject_id', $srcSubjectIds)
            ->orderBy('id')->chunkById(500, function ($chunk) use (&$plan, $subjects, $src, $imagesDir, $program, &$targets) {
                $levels = $this->levelsFor($src, $chunk->pluck('id')->all(), $plan['stats']);
                foreach ($chunk as $raw) {
                    $plan['stats']['rows_read']++;
                    try {
                        $norm = $this->normalizer->normalize((array) $raw);
                        foreach ($norm['items'] as $it) {
                            if ($it['skill_source_id'] !== null && ! isset($plan['known_skills'][$it['skill_source_id']])) {
                                $plan['stats']['unknown_skill']++;
                                throw new InvalidArgumentException("row {$raw->id}: skill {$it['skill_source_id']} not in source skills of the subject");
                            }
                        }
                        $image = $this->inspectImage($norm['image'], $imagesDir, $program, $targets);
                    } catch (InvalidArgumentException $e) {
                        if (! str_contains($e->getMessage(), 'not in source skills')) {
                            $plan['stats'][str_contains($e->getMessage(), 'image target') ? 'image_conflicts' : 'decode_failures']++;
                        }
                        $plan['errors'][] = $e->getMessage();

                        continue;
                    }
                    $plan['stats']['items_before_dedupe'] += count($norm['items']);
                    $plan['stats']['passages_before_dedupe'] += $norm['is_passage'] ? 1 : 0;
                    if ($norm['image'] && ! $image['exists']) {
                        $plan['stats']['images_missing']++;
                        $plan['missing_images'][] = ['source_id' => $norm['source_id'], 'path' => $norm['image']];
                    }
                    $spSubject = $subjects[(int) $raw->subject_id];
                    $plan['rows'][$norm['source_id']] = [
                        'norm' => $norm,
                        'sp_subject_id' => $spSubject->id,
                        'levels' => $levels[$norm['source_id']] ?? [],
                        'image' => $image,
                        'key' => DedupeKey::make($spSubject->id, $norm['items'], $image['md5'], $norm['passage_text']),
                        'merged' => [],
                        'is_duplicate' => false,
                    ];
                }
            });

        $this->dedupe($plan);

        return $plan;
    }

    /** @return array<int,list<int>> source id => grade levels */
    private function levelsFor(ConnectionInterface $src, array $ids, array &$stats): array
    {
        $out = [];
        $links = $src->table('classroom_question as cq')
            ->leftJoin('classrooms as c', 'c.id', '=', 'cq.classroom_id')
            ->leftJoin('grades as g', 'g.id', '=', 'c.Grade_id')
            ->whereIn('cq.question_id', $ids)
            ->get(['cq.question_id', 'c.id as classroom_id', 'c.Name_Class', 'g.school_type_id']);
        foreach ($links as $l) {
            if ($l->classroom_id === null || $l->school_type_id === null) {
                $stats['orphan_links']++;

                continue;
            }
            $grade = $this->levelMap->grade((int) $l->school_type_id, (string) $l->Name_Class);
            if ($grade === null) {
                $stats['unmapped_levels']++;

                continue;
            }
            $out[(int) $l->question_id][$grade] = $grade;
        }

        return array_map(fn ($g) => array_values($g), $out);
    }

    /** @return array{exists:bool, md5:?string, target:?string, size:?int, src:?string} */
    private function inspectImage(?string $path, string $imagesDir, string $program, array &$targets): array
    {
        if ($path === null) {
            return ['exists' => false, 'md5' => null, 'target' => null, 'size' => null, 'src' => null];
        }
        $full = rtrim($imagesDir, '/').'/'.ltrim($path, '/');
        if (! is_file($full)) {
            return ['exists' => false, 'md5' => null, 'target' => null, 'size' => null, 'src' => $full];
        }
        $md5 = md5_file($full);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $target = "special-programs/{$program}/{$name}.".($ext === 'jfif' ? 'jpg' : $ext);
        if (isset($targets[$target]) && $targets[$target] !== $md5) {
            throw new InvalidArgumentException("image target {$target} already used by a different file ({$path})");
        }
        $targets[$target] = $md5;

        return ['exists' => true, 'md5' => $md5, 'target' => $target, 'size' => filesize($full), 'src' => $full];
    }

    private function dedupe(array &$plan): void
    {
        $keys = array_map(fn ($r) => $r['key'], $plan['rows']);
        foreach (DedupeKey::duplicateGroups($keys) as $ids) {
            $merge = DedupeKey::merge(array_map(fn ($id) => [
                'id' => $id,
                'levels' => $plan['rows'][$id]['levels'],
                'term' => $plan['rows'][$id]['norm']['term'],
                'week' => $plan['rows'][$id]['norm']['week'],
            ], $ids));
            $s = $merge['survivor_id'];
            $plan['rows'][$s]['levels'] = $merge['levels'];
            $plan['rows'][$s]['norm']['term'] = $merge['term'];
            $plan['rows'][$s]['norm']['week'] = $merge['week'];
            $plan['rows'][$s]['merged'] = $merge['merged_source_ids'];
            foreach ($merge['merged_source_ids'] as $m) {
                $plan['rows'][$m]['is_duplicate'] = true;
            }
            $plan['dedupe_groups'][] = ['survivor' => $s, 'merged' => $merge['merged_source_ids']];
        }
    }

    // ───────────────────────────── pass 2 ─────────────────────────────

    private function write(ConnectionInterface $src, string $program, array $subjects, array &$plan, string $imagesDir, callable $log): void
    {
        $banks = [];
        foreach ($subjects as $subject) {
            $bank = $this->ownerBank($subject);
            $subject->update(['question_bank_id' => $bank->id]);
            $banks[$subject->id] = $bank;
        }

        $skillMap = [];
        foreach ($plan['skills'] as $i => $s) {
            $skill = SpSkill::updateOrCreate(['source_skill_id' => (int) $s->id], [
                'sp_subject_id' => $subjects[(int) $s->subject_id]->id,
                'name' => trim((string) $s->name),
                'term' => in_array($s->term, ['first', 'second', 'third'], true) ? $s->term : null,
                'sort_order' => $i,
            ]);
            $skillMap[(int) $s->id] = $skill->id;
        }

        $n = 0;
        foreach ($plan['rows'] as $id => $row) {
            if ($row['is_duplicate']) {
                continue;
            }
            $spSubject = collect($subjects)->first(fn ($s) => $s->id === $row['sp_subject_id']);
            $result = DB::transaction(fn () => $this->writeRow($program, $row, $banks[$spSubject->id], $skillMap));
            foreach ($result as $k => $v) {
                $plan['rows'][$id]['result'][$k] = $v;
            }
            if (++$n % 500 === 0) {
                $log("  {$program}: {$n} source rows written");
            }
        }
    }

    private function ownerBank(SpSubject $subject): QuestionBank
    {
        $name = "بنك {$subject->name_ar} — منصة الأول";

        return QuestionBank::unguarded(fn () => QuestionBank::firstOrCreate(
            ['category_type' => 'special_program', 'name_ar' => $name],
            [
                'school_id' => null, 'bank_type' => 'public', 'is_owner_bank' => 1, 'visibility' => 'public', 'status' => 'active',
                'source' => 'al_awwal', 'external_platform' => 'al_awwal', 'allow_images' => 1, 'allow_passage_questions' => 1,
                'allow_tahsili_questions' => $subject->program === 'tahsili' ? 1 : 0,
            ],
        ));
    }

    /** @return array{image:string, items:array<string,int>} */
    private function writeRow(string $program, array $row, QuestionBank $bank, array $skillMap): array
    {
        $norm = $row['norm'];
        $image = $row['image'];
        $imageResult = $this->copyImage($image);

        $passageId = null;
        if ($norm['is_passage']) {
            $passage = Passage::withTrashed()->updateOrCreate(
                ['question_bank_id' => $bank->id, 'passage_code' => 'AW-'.$norm['source_id']],
                ['passage_text' => $norm['passage_text'], 'difficulty_level' => $norm['difficulty'], 'status' => 'approved'],
            );
            $passageId = $passage->id;
        }

        $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
        foreach ($norm['items'] as $it) {
            $ext = $norm['is_passage'] ? $norm['source_id'].':'.$it['sub_index'] : (string) $norm['source_id'];
            $sig = $this->signature($row, $it);
            $existing = BankQuestion::withTrashed()->where('question_bank_id', $bank->id)
                ->where('external_platform', 'al_awwal')->where('external_id', $ext)->first();

            if ($existing && ($existing->metadata['sp_sig'] ?? null) === $sig && SpQuestion::where('bank_question_id', $existing->id)->exists()) {
                $counts['unchanged']++;

                continue;
            }

            $answers = $this->mapAnswers->execute(['type' => 'mcq', 'options_ar' => $it['options'], 'correct_index' => $it['correct_index']]);
            $attrs = [
                'question_bank_id' => $bank->id,
                'question_category' => $norm['is_passage'] ? 'passage' : ($program === 'tahsili' ? 'tahsili' : 'normal'),
                'question_content_type' => $image['target'] ? ($it['generic_stem'] ? 'image' : 'mixed') : 'text',
                'is_full_image_question' => $program === 'tahsili' && $image['target'] && $it['generic_stem'] ? 1 : 0,
                'passage_id' => $passageId,
                'type' => 'mcq',
                'body_ar' => $it['stem'],
                'answer_data' => $answers['json'],
                'difficulty' => $norm['difficulty'],
                'points' => 1,
                'attachment_path' => $image['target'],
                'source' => 'al_awwal',
                'status' => 'approved',
                'external_platform' => 'al_awwal',
                'external_id' => $ext,
                'metadata' => ['sp_sig' => $sig, 'source_id' => $norm['source_id']],
            ];
            if ($existing) {
                $existing->fill($attrs)->save();
                $q = $existing;
                $counts['updated']++;
            } else {
                $q = BankQuestion::create($attrs);
                $counts['created']++;
            }
            $this->createQuestion->syncAnswerRows($q, $answers['rows']);

            $sp = SpQuestion::updateOrCreate(['bank_question_id' => $q->id], [
                'sp_subject_id' => $row['sp_subject_id'],
                'sp_skill_id' => $it['skill_source_id'] !== null ? $skillMap[$it['skill_source_id']] : null,
                'passage_id' => $passageId,
                'sub_index' => $it['sub_index'],
                'difficulty' => ['easy', 'medium', 'hard'][$norm['difficulty'] - 1],
                'term' => $norm['term'],
                'week' => $norm['week'],
                'question_order' => $norm['question_order'],
                'has_image' => (bool) $image['target'],
                'content_hash' => $row['key'],
                'merged_source_ids' => $row['merged'] ?: null,
                'is_active' => true,
            ]);
            DB::table('sp_question_levels')->where('sp_question_id', $sp->id)->delete();
            DB::table('sp_question_levels')->insert(array_map(fn ($g) => ['sp_question_id' => $sp->id, 'grade_level' => $g], $row['levels']));

            if ($passageId) {
                DB::table('passage_questions')->updateOrInsert(
                    ['passage_id' => $passageId, 'question_id' => $q->id],
                    ['sort_order' => $it['sub_index']],
                );
            }
        }

        return ['image' => $imageResult, 'items' => $counts];
    }

    private function copyImage(array $image): string
    {
        if (! $image['target']) {
            return 'none';
        }
        $dest = storage_path('app/public/'.$image['target']);
        if (is_file($dest) && filesize($dest) === $image['size']) {
            return 'unchanged';
        }
        if (! is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0775, true);
        }
        copy($image['src'], $dest);

        return 'copied';
    }

    /** Stable across runs and independent of DB ids: everything that would change the written rows. */
    private function signature(array $row, array $item): string
    {
        $n = $row['norm'];

        return md5(json_encode([
            $row['sp_subject_id'], $item, $n['passage_text'], $n['difficulty'], $n['term'], $n['week'], $n['question_order'],
            $row['image']['target'], $row['image']['md5'], $row['levels'], $row['merged'], $row['key'],
        ], JSON_UNESCAPED_UNICODE));
    }

    // ───────────────────────────── dry run + report ─────────────────────────────

    /** Read-only: compare against what is already in the DB so a dry-run shows created/updated/unchanged too. */
    private function classifyDryRun(string $program, array $subjects, array &$plan): void
    {
        $bankBySubject = [];
        foreach ($subjects as $s) {
            $bankBySubject[$s->id] = $s->question_bank_id;
        }
        foreach ($plan['rows'] as $id => $row) {
            if ($row['is_duplicate']) {
                continue;
            }
            $bankId = $bankBySubject[$row['sp_subject_id']] ?? null;
            $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
            foreach ($row['norm']['items'] as $it) {
                $ext = $row['norm']['is_passage'] ? $id.':'.$it['sub_index'] : (string) $id;
                $existing = $bankId ? BankQuestion::withTrashed()->where('question_bank_id', $bankId)
                    ->where('external_platform', 'al_awwal')->where('external_id', $ext)->first() : null;
                $key = ! $existing ? 'created' : ((($existing->metadata['sp_sig'] ?? null) === $this->signature($row, $it)) ? 'unchanged' : 'updated');
                $counts[$key]++;
            }
            $img = $row['image']['target'];
            $dest = $img ? storage_path('app/public/'.$img) : null;
            $plan['rows'][$id]['result'] = [
                'items' => $counts,
                'image' => ! $img ? 'none' : ((is_file($dest) && filesize($dest) === $row['image']['size']) ? 'unchanged' : 'copied'),
            ];
        }
    }

    private function summarise(array $plan): array
    {
        $s = $plan['stats'];
        $out = $s + [
            'items_after_dedupe' => 0, 'passages_after_dedupe' => 0, 'sub_questions' => 0,
            'dedupe_groups' => count($plan['dedupe_groups']), 'dedupe_rows_merged' => 0,
            'items' => ['created' => 0, 'updated' => 0, 'unchanged' => 0],
            'images' => ['copied' => 0, 'unchanged' => 0],
            'level_histogram' => [],
        ];
        foreach ($plan['rows'] as $row) {
            if ($row['is_duplicate']) {
                $out['dedupe_rows_merged']++;

                continue;
            }
            $n = count($row['norm']['items']);
            $out['items_after_dedupe'] += $n;
            if ($row['norm']['is_passage']) {
                $out['passages_after_dedupe']++;
                $out['sub_questions'] += $n;
            }
            $res = $row['result'] ?? null;
            foreach (($res['items'] ?? []) as $k => $v) {
                $out['items'][$k] += $v;
            }
            if (in_array($res['image'] ?? null, ['copied', 'unchanged'], true)) {
                $out['images'][$res['image']]++;
            }
            foreach ($row['levels'] as $g) {
                $out['level_histogram'][$g] = ($out['level_histogram'][$g] ?? 0) + $n;
            }
        }
        ksort($out['level_histogram']);
        $out['dedupe_group_list'] = $plan['dedupe_groups'];
        $out['missing_images'] = $plan['missing_images'];
        $out['errors'] = $plan['errors'];

        return $out;
    }
}
