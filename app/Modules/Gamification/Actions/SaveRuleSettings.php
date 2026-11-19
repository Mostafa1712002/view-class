<?php

namespace App\Modules\Gamification\Actions;

use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Repositories\Contracts\PointSettingsRepository;
use App\Modules\Gamification\Support\RuleCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Saves the rules matrix. Platform: every wired rule. School: only rules the platform allows to override. */
final class SaveRuleSettings
{
    public function __construct(private PointSettingsRepository $settings) {}

    /** @param array<string, array> $input rules[key][field] from the matrix form */
    public function platform(array $input, int $actorId): void
    {
        foreach ($this->wired($input) as $key => $row) {
            $this->settings->save(PointRuleSetting::PLATFORM, $key, [
                'points' => $this->int($row['points'] ?? 0, $key, 'points') ?? 0,
                'period_points' => $this->int($row['period_points'] ?? null, $key, 'period_points'),
                'is_enabled' => ! empty($row['is_enabled']),
                'allow_school_override' => ! empty($row['allow_school_override']),
                'options' => $this->options($key, $row['options'] ?? []),
            ], $actorId);
        }
    }

    public function school(int $schoolId, array $input, int $actorId): void
    {
        $platform = $this->settings->allFor(PointRuleSetting::PLATFORM);
        foreach ($this->wired($input) as $key => $row) {
            if (! ($platform[$key]->allow_school_override ?? false)) {
                continue; // not overridable: ignored, never an error
            }
            Gate::authorize('editRuleOverride', [\App\Modules\Gamification\Models\PointTransaction::class, $key]);
            $this->settings->save($schoolId, $key, [
                'points' => $this->int($row['points'] ?? null, $key, 'points'),
                'period_points' => $this->int($row['period_points'] ?? null, $key, 'period_points'),
                // school can only narrow: null = inherit, false = off
                'is_enabled' => ! empty($row['disabled']) ? false : null,
                'options' => $this->options($key, $row['options'] ?? []) ?: null,
            ], $actorId);
        }
    }

    /** Only known, wired rules. */
    private function wired(array $input): array
    {
        return array_filter($input, fn ($row, $key) => is_array($row) && (RuleCatalog::get((string) $key)['wired'] ?? false), ARRAY_FILTER_USE_BOTH);
    }

    private function int(mixed $v, string $key, string $field): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (filter_var($v, FILTER_VALIDATE_INT) === false || (int) $v < 0 || (int) $v > 1000000) {
            throw ValidationException::withMessages(["rules.$key.$field" => __('points_manage.invalid_number')]);
        }

        return (int) $v;
    }

    /** Keeps only the options of the rule's schema, typed. */
    private function options(string $key, mixed $given): array
    {
        $out = [];
        foreach (RuleCatalog::get($key)['options'] as $name => $schema) {
            $v = is_array($given) ? ($given[$name] ?? null) : null;
            if ($v === null || $v === '') {
                continue;
            }
            $ok = match ($schema['type']) {
                'int' => filter_var($v, FILTER_VALIDATE_INT) !== false && (int) $v >= 0,
                'select' => in_array($v, $schema['values'], true),
                'date' => (bool) strtotime((string) $v),
                'datetime' => (bool) strtotime((string) $v),
                default => false,
            };
            if (! $ok) {
                throw ValidationException::withMessages(["rules.$key.options.$name" => __('points_manage.invalid_option')]);
            }
            $out[$name] = $schema['type'] === 'int' ? (int) $v : (string) $v;
        }

        return $out;
    }
}
