<?php

namespace App\Modules\SpecialPrograms\Actions\Import;

use InvalidArgumentException;

/**
 * Pure decoder for one «الأول» `questions` row (D§4.2 step 3). No DB, no disk.
 *
 * Input keys: id|source_id, is_independent, passage_text, question_text, options,
 * skills, skill_id, question_image, difficulty, term, week, question_order.
 */
final class AlAwwalRowNormalizer
{
    private const DIFFICULTY = ['easy' => 1, 'medium' => 2, 'hard' => 3];

    /** Generic image-question stems, after {@see self::loose()}. */
    private const GENERIC_STEMS = ['اختر', 'اختر الاجابة الصحيحة'];

    /**
     * @return array{
     *   source_id:int, is_passage:bool, passage_text:?string, difficulty:int, difficulty_key:string,
     *   term:?string, week:?int, question_order:?int, image:?string,
     *   items:list<array{sub_index:int, stem:string, options:list<string>, correct_index:int, skill_source_id:?int, generic_stem:bool}>
     * }
     */
    public function normalize(array $row): array
    {
        $id = (int) ($row['source_id'] ?? $row['id'] ?? 0);
        $isPassage = ($row['is_independent'] ?? 'independent') === 'passage';

        $texts = $this->decode($row['question_text'] ?? null, $id, 'question_text');
        $options = $this->decode($row['options'] ?? null, $id, 'options');
        $rowSkill = isset($row['skill_id']) && $row['skill_id'] !== '' ? (int) $row['skill_id'] : null;

        if (! $isPassage) {
            // Independent: one stem, one flat option list.
            $texts = [$texts[0] ?? null];
            $options = [$options];
            $skills = [$rowSkill];
        } else {
            $skills = $row['skills'] ?? null;
            $skills = ($skills === null || $skills === '') ? array_fill(0, count($texts), $rowSkill)
                : array_map(fn ($s) => $s === null || $s === '' ? $rowSkill : (int) $s, $this->decode($skills, $id, 'skills'));
        }

        if ($texts === [null] || $texts === [] || count($texts) !== count($options) || count($texts) !== count($skills)) {
            throw new InvalidArgumentException("row {$id}: question_text/options/skills do not line up");
        }

        $items = [];
        foreach ($texts as $i => $text) {
            $stem = $this->cleanStem((string) $text);
            if ($stem === '') {
                throw new InvalidArgumentException("row {$id}: empty stem at {$i}");
            }
            [$opts, $correct] = $this->options($options[$i], $id, $i);
            $items[] = [
                'sub_index' => $i,
                'stem' => $stem,
                'options' => $opts,
                'correct_index' => $correct,
                'skill_source_id' => $skills[$i],
                'generic_stem' => $this->isGenericStem($stem),
            ];
        }

        $diffKey = (string) ($row['difficulty'] ?? 'medium');
        if (! isset(self::DIFFICULTY[$diffKey])) {
            throw new InvalidArgumentException("row {$id}: unknown difficulty {$diffKey}");
        }
        $term = $row['term'] ?? null;
        $term = $term === 'third' ? 'both' : ($term === '' ? null : $term);
        $image = trim((string) ($row['question_image'] ?? ''));

        return [
            'source_id' => $id,
            'is_passage' => $isPassage,
            'passage_text' => $isPassage ? trim((string) ($row['passage_text'] ?? '')) : null,
            'difficulty' => self::DIFFICULTY[$diffKey],
            'difficulty_key' => $diffKey,
            'term' => $term,
            'week' => isset($row['week']) && $row['week'] !== '' ? (int) $row['week'] : null,
            'question_order' => isset($row['question_order']) && $row['question_order'] !== '' ? (int) $row['question_order'] : null,
            'image' => $image !== '' ? $image : null,
            'items' => $items,
        ];
    }

    public function isGenericStem(string $stem): bool
    {
        return in_array($this->loose($stem), self::GENERIC_STEMS, true);
    }

    /** Decode a JSON column, looping while the result is still a string (double encoding), capped at 5. */
    private function decode(mixed $value, int $id, string $col): array
    {
        for ($n = 0; is_string($value); $n++) {
            if ($n >= 5) {
                throw new InvalidArgumentException("row {$id}: {$col} still a string after 5 decodes");
            }
            $value = json_decode($value, true);
            if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException("row {$id}: {$col} is not valid JSON");
            }
        }
        if (! is_array($value)) {
            throw new InvalidArgumentException("row {$id}: {$col} is not a JSON array");
        }

        return array_values($value);
    }

    /** @return array{0:list<string>, 1:int} */
    private function options(mixed $group, int $id, int $i): array
    {
        if (! is_array($group) || $group === []) {
            throw new InvalidArgumentException("row {$id}: no options at {$i}");
        }
        $texts = [];
        $correct = [];
        foreach (array_values($group) as $k => $opt) {
            $texts[] = trim((string) ($opt['text'] ?? ''));
            if (! empty($opt['is_correct'])) {
                $correct[] = $k;
            }
        }
        if (count($correct) !== 1) {
            throw new InvalidArgumentException("row {$id}: expected exactly 1 correct option at {$i}, got ".count($correct));
        }

        return [$texts, $correct[0]];
    }

    private function cleanStem(string $s): string
    {
        $s = trim($s);
        if (str_starts_with($s, '["') && str_ends_with($s, '"]')) {
            $s = substr($s, 2, -2);
        }

        return trim(str_replace('\n', "\n", $s));
    }

    /** Hamza forms folded, tashkeel dropped, whitespace collapsed. */
    private function loose(string $s): string
    {
        $s = preg_replace('/[\x{064B}-\x{065F}\x{0640}]/u', '', $s);
        $s = strtr($s, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا']);

        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
