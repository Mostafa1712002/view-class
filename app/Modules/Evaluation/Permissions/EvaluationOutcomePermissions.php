<?php

namespace App\Modules\Evaluation\Permissions;

/**
 * Phase C (#205) — Permission constants for educational outcome management.
 *
 * TODO Phase D: register these constants in the granular permission catalog
 * (app/Modules/Evaluation/Permissions/ permission seeder + middleware gates)
 * so they can be assigned individually to school roles.
 */
class EvaluationOutcomePermissions
{
    /**
     * Allows a user to view and change the school-level outcome averaging method
     * (eval.outcome_method setting). Super-admins may also change the global default.
     */
    const MANAGE_OUTCOME_METHOD = 'eval.manage_outcome_method';
}
