<?php

namespace App\Modules\Canteen\Services;

use App\Models\CanteenBalance;
use App\Models\CanteenBalanceTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CanteenBalanceService
{
    /**
     * Apply a balance change atomically and always log it.
     *
     * @param  string  $type  add | deduct | set
     * @param  string  $source  admin | order | refund
     * @throws RuntimeException when a deduct/set would make the balance negative
     */
    public function apply(int $studentId, ?int $schoolId, string $type, float $amount, ?string $note, string $source, ?int $performedBy): CanteenBalance
    {
        $amount = round(abs($amount), 2);

        return DB::transaction(function () use ($studentId, $schoolId, $type, $amount, $note, $source, $performedBy) {
            $balance = CanteenBalance::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = CanteenBalance::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'balance' => 0,
                ]);
            }

            $current = (float) $balance->balance;
            $new = match ($type) {
                'add' => $current + $amount,
                'deduct' => $current - $amount,
                'set' => $amount,
                default => throw new RuntimeException('invalid_type'),
            };

            if ($new < 0) {
                throw new RuntimeException('insufficient_balance');
            }

            $balance->balance = round($new, 2);
            $balance->save();

            CanteenBalanceTransaction::create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balance->balance,
                'note' => $note,
                'source' => $source,
                'performed_by' => $performedBy,
            ]);

            return $balance;
        });
    }
}
