<?php

namespace App\Modules\SpecialPrograms\Actions\Picking;

final class Apportion
{
    /**
     * Split $n questions across skills by percentage share.
     * Each skill gets >= 1 while skills <= n; with more skills than n only the n largest get 1.
     *
     * @param  array<int,float|int>  $shares  skillId => percentage
     * @return array<int,int> skillId => count (sums to $n when $shares is non-empty and $n > 0)
     */
    public static function bySkill(array $shares, int $n): array
    {
        if (! $shares || $n <= 0) {
            return array_fill_keys(array_keys($shares), 0);
        }
        $ids = array_keys($shares);
        if (count($ids) > $n) {
            usort($ids, fn ($a, $b) => $shares[$b] <=> $shares[$a]); // stable: ties keep input order
            $top = array_slice($ids, 0, $n);

            return array_replace(array_fill_keys(array_keys($shares), 0), array_fill_keys($top, 1));
        }
        if (array_sum($shares) <= 0) {
            $shares = array_fill_keys($ids, 1);
        }
        $sum = array_sum($shares);
        $counts = [];
        foreach ($shares as $id => $p) {
            $counts[$id] = max(1, (int) round($p / $sum * $n));
        }
        $diff = $n - array_sum($counts);
        while ($diff !== 0) {
            arsort($counts); // largest first, stable for ties
            $moved = false;
            foreach ($counts as $id => $c) {
                if ($diff > 0) {
                    $counts[$id]++;
                    $diff--;
                } elseif ($c > 1) {
                    $counts[$id]--;
                    $diff++;
                } else {
                    continue;
                }
                $moved = true;
                if ($diff === 0) {
                    break;
                }
            }
            if (! $moved) {
                break;
            }
        }

        return $counts;
    }
}
