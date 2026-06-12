<?php

namespace App\Modules\Evaluation\Actions\Concerns;

use App\Models\Evaluation;
use App\Models\EvaluationResponse;
use App\Modules\Evaluation\Enums\FormType;

/**
 * Shared logic for persisting an evaluator's answers into EvaluationResponse rows
 * in the EXACT shape each scoring strategy requires:
 *
 *  - Rubric:       ONE row per ITEM      → indicator_id = NULL, level_id = chosen level.
 *  - Rating scale: ONE row per INDICATOR → indicator_id + level_id set.
 *  - Checklist:    ONE row per INDICATOR → indicator_id + checklist_value (bool).
 *
 * Writing rows any other way makes the scorer return 0 with no error, so this is
 * the single place that shape is enforced for both draft-save and submit.
 */
trait WritesResponses
{
    /**
     * Re-write all responses for the evaluation from the submitted answers.
     * Validity is bounded by the FROZEN snapshot (unknown item/indicator/level
     * ids are ignored), never the live form.
     *
     * @param array $answers Raw request input:
     *   - rubric:       ['items' => [itemId => levelId], 'item_notes' => [itemId => note]]
     *   - rating_scale: ['indicators' => [indicatorId => levelId], 'item_notes' => [...]]
     *   - checklist:    ['indicators' => [indicatorId => '1'|'0'], 'item_notes' => [...]]
     */
    protected function syncResponses(Evaluation $evaluation, array $snapshotPayload, FormType $type, array $answers): void
    {
        $items      = $this->snapshotItems($snapshotPayload);
        $levelIds   = $this->snapshotLevelIds($snapshotPayload);
        $itemNotes  = is_array($answers['item_notes'] ?? null) ? $answers['item_notes'] : [];

        // Clear previous responses; we re-persist the full current answer set.
        $evaluation->responses()->delete();

        $rows = [];

        if ($type === FormType::Rubric) {
            $chosen = is_array($answers['items'] ?? null) ? $answers['items'] : [];
            foreach ($items as $itemId => $item) {
                $levelId = isset($chosen[$itemId]) ? (int) $chosen[$itemId] : null;
                $note    = $this->note($itemNotes, $itemId);
                if ($levelId === null && $note === null) {
                    continue;
                }
                $rows[] = [
                    'item_id'      => $itemId,
                    'indicator_id' => null,
                    'level_id'     => $levelId !== null && in_array($levelId, $levelIds, true) ? $levelId : null,
                    'note'         => $note,
                ];
            }
        } elseif ($type === FormType::Percentage) {
            // One row per item; the evaluator's 0–100 percentage is stored on `score`.
            $chosen = is_array($answers['items'] ?? null) ? $answers['items'] : [];
            foreach ($items as $itemId => $item) {
                $hasVal = array_key_exists($itemId, $chosen) && $chosen[$itemId] !== '' && $chosen[$itemId] !== null;
                $score  = $hasVal ? max(0.0, min(100.0, (float) $chosen[$itemId])) : null;
                $note   = $this->note($itemNotes, $itemId);
                if ($score === null && $note === null) {
                    continue;
                }
                $rows[] = [
                    'item_id'      => $itemId,
                    'indicator_id' => null,
                    'level_id'     => null,
                    'score'        => $score,
                    'note'         => $note,
                ];
            }
        } else {
            // rating_scale + checklist: one row per indicator
            $chosen = is_array($answers['indicators'] ?? null) ? $answers['indicators'] : [];
            foreach ($items as $itemId => $item) {
                foreach ($item['indicators'] as $indicatorId => $indicator) {
                    if (!array_key_exists($indicatorId, $chosen)) {
                        continue;
                    }
                    $raw = $chosen[$indicatorId];
                    if ($type === FormType::Checklist) {
                        $rows[] = [
                            'item_id'         => $itemId,
                            'indicator_id'    => $indicatorId,
                            'level_id'        => null,
                            'checklist_value' => (bool) ((int) $raw === 1 || $raw === '1' || $raw === true),
                        ];
                    } else {
                        $levelId = (int) $raw;
                        if ($levelId <= 0 || !in_array($levelId, $levelIds, true)) {
                            continue;
                        }
                        $rows[] = [
                            'item_id'      => $itemId,
                            'indicator_id' => $indicatorId,
                            'level_id'     => $levelId,
                        ];
                    }
                }
            }
        }

        foreach ($rows as $row) {
            EvaluationResponse::create(array_merge(['evaluation_id' => $evaluation->id], $row));
        }
    }

    /**
     * Active items from the snapshot keyed by item id, each with its active
     * indicators keyed by indicator id.
     *
     * @return array<int,array{name:?string,weight:float,is_required:bool,evidence_required:bool,needs_evidence:bool,indicators:array<int,array<string,mixed>>}>
     */
    protected function snapshotItems(array $payload): array
    {
        $out = [];
        foreach (($payload['items'] ?? []) as $item) {
            if (!is_array($item) || ($item['status'] ?? 'active') === 'disabled') {
                continue;
            }
            $indicators = [];
            foreach (($item['indicators'] ?? []) as $ind) {
                if (!is_array($ind) || ($ind['status'] ?? 'active') === 'disabled') {
                    continue;
                }
                $indicators[(int) $ind['id']] = $ind;
            }
            $out[(int) $item['id']] = [
                'name'              => $item['name'] ?? null,
                'weight'            => (float) ($item['weight'] ?? 0),
                'is_required'       => (bool) ($item['is_required'] ?? false),
                'needs_evidence'    => (bool) ($item['needs_evidence'] ?? false),
                'evidence_required' => (bool) ($item['evidence_required'] ?? false),
                'indicators'        => $indicators,
            ];
        }

        return $out;
    }

    /** @return int[] */
    protected function snapshotLevelIds(array $payload): array
    {
        $ids = [];
        foreach (($payload['levels'] ?? []) as $level) {
            if (is_array($level) && isset($level['id'])) {
                $ids[] = (int) $level['id'];
            }
        }

        return $ids;
    }

    /** @param array<int|string,mixed> $notes */
    private function note(array $notes, int $itemId): ?string
    {
        $val = $notes[$itemId] ?? null;
        $val = is_string($val) ? trim($val) : null;

        return ($val === null || $val === '') ? null : $val;
    }
}
