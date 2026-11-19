<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\User;
use App\Modules\RawaniHub\DTOs\BeneficiaryData;
use Illuminate\Support\Facades\DB;

/** A parent adds a child (self-service): the student is created in the default school and linked to the parent. */
final class AddChild
{
    public function __construct(private FindOrCreateBeneficiary $beneficiaries) {}

    /** @throws NationalIdCollision when the child's national ID already belongs to a user */
    public function execute(User $parent, BeneficiaryData $child): User
    {
        return DB::transaction(function () use ($parent, $child) {
            $student = $this->beneficiaries->execute($child, selfService: true);
            // Linked explicitly to THIS parent (not looked up by national ID, which is not DB-unique).
            DB::table('parent_student')->insertOrIgnore([
                'parent_id' => $parent->id, 'student_id' => $student->id, 'relationship' => 'parent',
                'is_primary' => $student->parents()->count() === 0 ? 1 : 0, 'can_receive_notifications' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $student;
        });
    }
}
