<?php

namespace App\Modules\SpecialPrograms\Actions\Import;

/** D§4.4: content identity of a source row, plus the survivor merge rules. */
final class DedupeKey
{
    /** Trim, collapse whitespace, strip tashkeel and tatweel. */
    public static function text(?string $s): string
    {
        $s = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', (string) $s);

        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /**
     * @param  list<array{stem:string, options:list<string>, correct_index:int, skill_source_id:?int}>  $items
     */
    public static function make(int $spSubjectId, array $items, ?string $imageMd5, ?string $passageText): string
    {
        $parts = [];
        foreach ($items as $it) {
            $parts[] = [
                $it['skill_source_id'],
                self::text($it['stem']),
                array_map(fn ($o, $k) => [self::text($o), $k === $it['correct_index']], $it['options'], array_keys($it['options'])),
            ];
        }

        return md5(json_encode([$spSubjectId, $parts, $imageMd5, self::text($passageText)], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Group ids by key; each group sorted ascending (survivor = first).
     *
     * @param  array<int,string>  $keysById
     * @return array<string,list<int>> only groups with 2+ members
     */
    public static function duplicateGroups(array $keysById): array
    {
        $groups = [];
        foreach ($keysById as $id => $key) {
            $groups[$key][] = (int) $id;
        }
        $groups = array_filter($groups, fn ($g) => count($g) > 1);
        foreach ($groups as &$g) {
            sort($g);
        }

        return $groups;
    }

    /**
     * Fold a group (survivor first) into the survivor.
     *
     * @param  list<array{id:int, levels:list<int>, term:?string, week:?int}>  $members  any order
     * @return array{survivor_id:int, levels:list<int>, term:?string, week:?int, merged_source_ids:list<int>}
     */
    public static function merge(array $members): array
    {
        usort($members, fn ($a, $b) => $a['id'] <=> $b['id']);
        $survivor = $members[0];
        $levels = [];
        $weeks = [];
        $terms = [];
        foreach ($members as $m) {
            $levels = array_merge($levels, $m['levels']);
            $terms[$m['term'] ?? ''] = true;
            if ($m['week'] !== null) {
                $weeks[] = $m['week'];
            }
        }
        $levels = array_values(array_unique($levels));
        sort($levels);
        $term = count($terms) > 1 ? 'both' : $survivor['term'];

        return [
            'survivor_id' => $survivor['id'],
            'levels' => $levels,
            'term' => $term,
            'week' => $weeks ? min($weeks) : null,
            'merged_source_ids' => array_slice(array_column($members, 'id'), 1),
        ];
    }
}
