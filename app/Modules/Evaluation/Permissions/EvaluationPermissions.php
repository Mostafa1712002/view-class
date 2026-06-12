<?php

namespace App\Modules\Evaluation\Permissions;

/**
 * Phase D (#208/#210) — Granular evaluation permission catalog.
 *
 * Each constant is a permission `slug` stored in the `permissions` table under
 * group 'evaluation' and mapped to roles via the `permission_role` pivot (the
 * table App\Models\Role::hasPermission() reads). Checked through
 * App\Models\User::canEval($slug), which short-circuits to true for super-admin
 * so existing admin access is never lost.
 */
final class EvaluationPermissions
{
    public const GROUP = 'evaluation';

    // Evaluation lifecycle
    public const VIEW                 = 'eval.view';
    public const CREATE               = 'eval.create';
    public const EDIT                 = 'eval.edit';
    public const DELETE_UNAPPROVED    = 'eval.delete_unapproved';

    // Item visibility / evaluation
    public const VIEW_ALL_ITEMS       = 'eval.view_all_items';
    public const VIEW_MY_ITEMS        = 'eval.view_my_items';
    public const EVALUATE_MY_ITEM     = 'eval.evaluate_my_item';
    public const EDIT_MY_ITEM         = 'eval.edit_my_item';

    // Item review cycle
    public const SEND_TO_REVIEW       = 'eval.send_to_review';
    public const APPROVE_ITEM         = 'eval.approve_item';
    public const REJECT_ITEM          = 'eval.reject_item';
    public const RETURN_ITEM          = 'eval.return_item';

    // Final evaluation
    public const APPROVE_FINAL        = 'eval.approve_final';
    public const REJECT_FINAL         = 'eval.reject_final';
    public const REOPEN_AFTER_SUBMIT  = 'eval.reopen_after_submit';
    public const EDIT_AFTER_APPROVAL  = 'eval.edit_after_approval';

    // Form / weight / outcome management
    public const MANAGE_ITEM_WEIGHTS  = 'eval.manage_item_weights';
    public const MANAGE_FORMS         = 'eval.manage_forms';
    public const MANAGE_OUTCOME_METHOD = 'eval.manage_outcome_method';
    public const RECOMPUTE_OUTCOME    = 'eval.recompute_outcome';

    // Evidence
    public const UPLOAD_EVIDENCE      = 'eval.upload_evidence';
    public const APPROVE_EVIDENCE     = 'eval.approve_evidence';
    public const REJECT_EVIDENCE      = 'eval.reject_evidence';
    public const DELETE_EVIDENCE      = 'eval.delete_evidence';
    public const DELETE_OTHERS_EVIDENCE = 'eval.delete_others_evidence';
    public const EDIT_EVIDENCE_AFTER_APPROVAL = 'eval.edit_evidence_after_approval';

    // Cross-cutting
    public const VIEW_AUDIT           = 'eval.view_audit';
    public const EXPORT               = 'eval.export';
    public const PRINT                = 'eval.print';

    /**
     * @return array<string,string> slug => Arabic display name
     */
    public static function all(): array
    {
        return [
            self::VIEW                 => 'عرض تقييمات الأداء',
            self::CREATE               => 'إنشاء تقييم أداء',
            self::EDIT                 => 'تعديل تقييم أداء',
            self::DELETE_UNAPPROVED    => 'حذف تقييم غير معتمد',
            self::VIEW_ALL_ITEMS       => 'عرض كل بنود التقييم',
            self::VIEW_MY_ITEMS        => 'عرض البنود المسندة لي فقط',
            self::EVALUATE_MY_ITEM     => 'تقييم بند مسند لي',
            self::EDIT_MY_ITEM         => 'تعديل بند مسند لي',
            self::SEND_TO_REVIEW       => 'إرسال بند للمراجعة',
            self::APPROVE_ITEM         => 'اعتماد بند',
            self::REJECT_ITEM          => 'رفض بند',
            self::RETURN_ITEM          => 'إعادة بند للمراجعة',
            self::APPROVE_FINAL        => 'اعتماد التقييم النهائي',
            self::REJECT_FINAL         => 'رفض التقييم النهائي',
            self::REOPEN_AFTER_SUBMIT  => 'فتح تقييم بعد الإرسال',
            self::EDIT_AFTER_APPROVAL  => 'تعديل تقييم بعد الاعتماد',
            self::MANAGE_ITEM_WEIGHTS  => 'إدارة أوزان البنود',
            self::MANAGE_FORMS         => 'إدارة نماذج التقييم',
            self::MANAGE_OUTCOME_METHOD => 'إدارة طريقة احتساب الناتج التعليمي',
            self::RECOMPUTE_OUTCOME    => 'إعادة احتساب الناتج التعليمي',
            self::UPLOAD_EVIDENCE      => 'رفع شواهد',
            self::APPROVE_EVIDENCE     => 'اعتماد شواهد',
            self::REJECT_EVIDENCE      => 'رفض شواهد',
            self::DELETE_EVIDENCE      => 'حذف شواهد',
            self::DELETE_OTHERS_EVIDENCE => 'حذف شواهد مستخدم آخر',
            self::EDIT_EVIDENCE_AFTER_APPROVAL => 'تعديل شواهد بعد الاعتماد',
            self::VIEW_AUDIT           => 'عرض سجلات التدقيق',
            self::EXPORT               => 'تصدير التقييمات',
            self::PRINT                => 'طباعة التقييمات',
        ];
    }

    /** Slugs only. @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }
}
