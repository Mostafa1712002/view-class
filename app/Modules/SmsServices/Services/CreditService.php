<?php

namespace App\Modules\SmsServices\Services;

use App\Modules\SmsServices\Models\SchoolSmsSetting;
use App\Modules\SmsServices\Models\SmsCreditLedger;
use Illuminate\Support\Facades\DB;

/**
 * Credit balance accounting for SMS (Trello #239 deduction + #243 ledger).
 *
 * Balance lives on school_sms_settings (sms_total = granted, sms_used =
 * consumed). available = sms_total - sms_used. Every movement also writes an
 * immutable sms_credit_ledger row so reports/audits are exact.
 */
final class CreditService
{
    public function available(SchoolSmsSetting $setting): int
    {
        return max(0, (int) $setting->sms_total - (int) $setting->sms_used);
    }

    /**
     * Deduct credit for a send. Returns the ledger row, or null if there is
     * insufficient credit (caller must not send in that case).
     */
    public function deduct(SchoolSmsSetting $setting, int $amount, string $reason, ?int $userId = null, ?string $refType = null, ?int $refId = null): ?SmsCreditLedger
    {
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($setting, $amount, $reason, $userId, $refType, $refId) {
            $setting = SchoolSmsSetting::lockForUpdate()->find($setting->id);
            $before  = $this->available($setting);

            if ($before < $amount) {
                return null; // insufficient — caller decides
            }

            $setting->increment('sms_used', $amount);
            $after = $this->available($setting->refresh());

            return SmsCreditLedger::create([
                'school_id'      => $setting->school_id,
                'type'           => 'deduction',
                'balance_before' => $before,
                'amount'         => -$amount,
                'balance_after'  => $after,
                'reason'         => $reason,
                'reference_type' => $refType,
                'reference_id'   => $refId,
                'user_id'        => $userId,
            ]);
        });
    }

    /** Add credit (e.g. recharge approval). */
    public function recharge(SchoolSmsSetting $setting, int $amount, string $reason, ?int $userId = null, ?string $refType = null, ?int $refId = null): SmsCreditLedger
    {
        return DB::transaction(function () use ($setting, $amount, $reason, $userId, $refType, $refId) {
            $setting = SchoolSmsSetting::lockForUpdate()->find($setting->id);
            $before  = $this->available($setting);

            $setting->increment('sms_total', $amount);
            $after = $this->available($setting->refresh());

            return SmsCreditLedger::create([
                'school_id'      => $setting->school_id,
                'type'           => 'recharge',
                'balance_before' => $before,
                'amount'         => $amount,
                'balance_after'  => $after,
                'reason'         => $reason,
                'reference_type' => $refType,
                'reference_id'   => $refId,
                'user_id'        => $userId,
            ]);
        });
    }
}
