<?php

namespace App\Modules\RawaniHub\Services;

use App\Models\User;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubProfile;

/**
 * Pre-checkout check (design §6 step 1): the same required-fields test the sync job runs, evaluated before any invoice
 * exists, so a buyer never pays into `needs_info`.
 */
class CheckoutReadiness
{
    /** Student fields and parent fields the buyer can fill themselves; anything else needs support. */
    public const FIXABLE_STUDENT = ['national_id', 'gender', 'stage', 'grade'];

    public const FIXABLE_PARENT = ['national_id'];

    public function __construct(private AccountStateBuilder $builder) {}

    /** @return array{student: list<string>, parent: list<string>} missing field names per role, empty when ready */
    public function missing(HubPackage $package, User $beneficiary): array
    {
        $parent = $beneficiary->parents()->orderByDesc('parent_student.is_primary')->orderBy('parent_student.id')->first();
        $profile = HubProfile::find($beneficiary->id);
        $out = ['student' => [], 'parent' => []];

        foreach (HubPlatform::whereIn('id', $package->items->pluck('platform_id')->unique())->get() as $platform) {
            $student = new HubPlatformAccount(['role' => 'student', 'managed' => true, 'enabled' => true]);
            $out['student'] = array_merge($out['student'], $this->builder->build($student, $platform, $beneficiary, $profile, [], now(), $parent)->missing);

            if ($platform->capabilities['parents'] ?? false) {
                if (! $parent) {
                    $out['parent'][] = 'parent';

                    continue;
                }
                $row = new HubPlatformAccount(['role' => 'parent', 'managed' => true, 'enabled' => true]);
                $out['parent'] = array_merge($out['parent'], $this->builder->build($row, $platform, $parent, null, [], now())->missing);
            }
        }

        return ['student' => array_values(array_unique($out['student'])), 'parent' => array_values(array_unique($out['parent']))];
    }

    public function ready(array $missing): bool
    {
        return $missing['student'] === [] && $missing['parent'] === [];
    }

    /** True when every missing field is one the buyer can fill in on the form. */
    public function fixableBy(array $missing, User $viewer, User $beneficiary): bool
    {
        if (array_diff($missing['student'], self::FIXABLE_STUDENT) !== []) {
            return false;
        }
        // Only a parent can complete the parent's own ID; a student buyer is told to contact support.
        return $missing['parent'] === []
            || (array_diff($missing['parent'], self::FIXABLE_PARENT) === [] && $viewer->id !== $beneficiary->id);
    }
}
