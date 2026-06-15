<?php

use App\Modules\QuestionBankCore\Controllers\CompoundController;
use App\Modules\QuestionBankCore\Controllers\ExamController;
use App\Modules\QuestionBankCore\Controllers\ImportController;
use App\Modules\QuestionBankCore\Controllers\PassageController;
use App\Modules\QuestionBankCore\Controllers\QuestionController;
use App\Modules\QuestionBankCore\Controllers\ScopeSelectorController;
use App\Modules\QuestionBankCore\Controllers\SkillController;
use App\Modules\QuestionBankCore\Controllers\StandardController;
use App\Modules\QuestionBankCore\Controllers\WeekController;
use Illuminate\Support\Facades\Route;

/**
 * Question-Bank rebuild — CORE screens (#249 selector, #250 add question,
 * #253 list/filters/actions).
 *
 * Additive: new URL prefix (admin/qb) + new route names (admin.qb.*). The legacy
 * admin/question-banks feature is untouched.
 *
 * Access: role middleware gives a deterministic 403 to out-of-role users (canDo
 * default-allows .view, so role middleware is the real read gate). Each action
 * also calls canDo(...) for the specific permission. Writes are restricted to
 * admins by role; canDo refines per job title. School scope is enforced in the
 * controllers via scopedSchoolId() (fail-closed).
 *
 * Wired in routes/web.php via:
 *   require __DIR__.'/../app/Modules/QuestionBankCore/Routes/web.php';
 */

// Reads (super-admin / school-admin / teacher)
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('admin/qb')
    ->name('admin.qb.')
    ->group(function () {
        // #249 — school/grade selector + AJAX cascade
        Route::get('scope', [ScopeSelectorController::class, 'index'])->name('scope.index');
        Route::get('scope/school/{schoolId}', [ScopeSelectorController::class, 'school'])->name('scope.school');
        Route::get('scope/school/{schoolId}/classes', [ScopeSelectorController::class, 'classes'])->name('scope.classes');
        Route::get('scope/semester/{semesterId}/weeks', [ScopeSelectorController::class, 'weeks'])->name('scope.weeks');
        Route::get('scope/skills', [ScopeSelectorController::class, 'skills'])->name('scope.skills');

        // #253 — questions list + read-only fragments
        Route::get('questions', [QuestionController::class, 'index'])->name('questions.index');
        Route::get('questions/{questionId}/answer', [QuestionController::class, 'answer'])->name('questions.answer');
        Route::get('questions/{questionId}/classes', [QuestionController::class, 'classes'])->name('questions.classes');
    });

// Writes (admins only by role; canDo refines per job title)
Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/qb')
    ->name('admin.qb.')
    ->group(function () {
        // #250 — manual create/edit
        Route::get('questions/create', [QuestionController::class, 'create'])->name('questions.create');
        Route::post('questions', [QuestionController::class, 'store'])->name('questions.store');
        Route::get('questions/{questionId}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
        Route::put('questions/{questionId}', [QuestionController::class, 'update'])->name('questions.update');

        // #253 — row actions
        Route::post('questions/{questionId}/duplicate', [QuestionController::class, 'duplicate'])->name('questions.duplicate');
        Route::post('questions/{questionId}/archive', [QuestionController::class, 'archive'])->name('questions.archive');
        Route::delete('questions/{questionId}', [QuestionController::class, 'destroy'])->name('questions.destroy');

        // #256 — review workflow transitions (canDo: edit / approve / reject)
        Route::post('questions/{questionId}/submit', [QuestionController::class, 'submit'])->name('questions.submit');
        Route::post('questions/{questionId}/approve', [QuestionController::class, 'approve'])->name('questions.approve');
        Route::post('questions/{questionId}/reject', [QuestionController::class, 'reject'])->name('questions.reject');

        // #252 — passages (reading-comprehension) CRUD + child attachment
        Route::get('passages', [PassageController::class, 'index'])->name('passages.index');
        Route::get('passages/create', [PassageController::class, 'create'])->name('passages.create');
        Route::post('passages', [PassageController::class, 'store'])->name('passages.store');
        Route::get('passages/{passageId}', [PassageController::class, 'show'])->name('passages.show');
        Route::get('passages/{passageId}/edit', [PassageController::class, 'edit'])->name('passages.edit');
        Route::put('passages/{passageId}', [PassageController::class, 'update'])->name('passages.update');
        Route::delete('passages/{passageId}', [PassageController::class, 'destroy'])->name('passages.destroy');
        Route::get('passages/{passageId}/questions/create', [PassageController::class, 'createQuestion'])->name('passages.questions.create');
        Route::post('passages/{passageId}/questions', [PassageController::class, 'storeQuestion'])->name('passages.questions.store');
        Route::delete('passages/{passageId}/questions/{questionId}', [PassageController::class, 'detachQuestion'])->name('passages.questions.detach');

        // #254 — Excel import (gated by canDo question_banks.import in the controller)
        Route::get('import', [ImportController::class, 'index'])->name('import.index');
        Route::get('import/template', [ImportController::class, 'template'])->name('import.template');
        Route::post('import/preview', [ImportController::class, 'preview'])->name('import.preview');
        Route::post('import/{batchId}/confirm', [ImportController::class, 'confirm'])->name('import.confirm');
        Route::get('import/{batchId}/errors', [ImportController::class, 'errorReport'])->name('import.errors');

        // #255 — electronic & paper exams linked to bank questions (gated by exams.* in controller)
        Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
        Route::get('exams/create', [ExamController::class, 'create'])->name('exams.create');
        Route::post('exams', [ExamController::class, 'store'])->name('exams.store');
        Route::get('exams/{examId}', [ExamController::class, 'show'])->name('exams.show');
        Route::get('exams/{examId}/results', [ExamController::class, 'results'])->name('exams.results');
        Route::post('exams/{examId}/publish', [ExamController::class, 'publish'])->name('exams.publish');
        Route::post('exams/{examId}/unpublish', [ExamController::class, 'unpublish'])->name('exams.unpublish');
        Route::delete('exams/{examId}', [ExamController::class, 'destroy'])->name('exams.destroy');

        // Bank-question picker → snapshot copy into qb_exam_questions
        Route::get('exams/{examId}/picker', [ExamController::class, 'picker'])->name('exams.picker');
        Route::post('exams/{examId}/picker', [ExamController::class, 'addFromBank'])->name('exams.add-from-bank');
        Route::delete('exams/{examId}/questions/{examQuestionId}', [ExamController::class, 'removeQuestion'])->name('exams.questions.remove');
    });

/**
 * #248 — Educational taxonomy management (المهارات / المعايير / المجمعات /
 * الأسابيع الدراسية). Routes named admin.qb.{skills,standards,compounds,weeks}.*
 *
 * Gating:
 *   - Skills + Weeks are school-scoped (skills carry school_id; weeks scope via
 *     term → academic_year.school_id), so school-admin may manage their own → the
 *     group allows role:super-admin,school-admin and each action calls canDo().
 *   - Standards + Compounds are GLOBAL (no school_id; compounds span schools), so
 *     management is super-admin only — a school-admin can't mutate shared data.
 * Reads default-allow on *.view via canDo(); the role middleware is the hard gate
 * for teacher/no-perm users (→ 403). Writes fail closed per canDo().
 */
Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/qb')
    ->name('admin.qb.')
    ->group(function () {
        // المهارات — CRUD + Excel import (#248)
        Route::get('skills', [SkillController::class, 'index'])->name('skills.index');
        Route::get('skills/create', [SkillController::class, 'create'])->name('skills.create');
        Route::post('skills', [SkillController::class, 'store'])->name('skills.store');
        Route::get('skills/import', [SkillController::class, 'importIndex'])->name('skills.import.index');
        Route::get('skills/import/template', [SkillController::class, 'importTemplate'])->name('skills.import.template');
        Route::post('skills/import/preview', [SkillController::class, 'importPreview'])->name('skills.import.preview');
        Route::post('skills/import/{batchId}/confirm', [SkillController::class, 'importConfirm'])->name('skills.import.confirm');
        Route::get('skills/{skillId}/edit', [SkillController::class, 'edit'])->name('skills.edit');
        Route::put('skills/{skillId}', [SkillController::class, 'update'])->name('skills.update');
        Route::delete('skills/{skillId}', [SkillController::class, 'destroy'])->name('skills.destroy');

        // الأسابيع الدراسية — list/bulk per term (#248)
        Route::get('weeks', [WeekController::class, 'index'])->name('weeks.index');
        Route::post('weeks', [WeekController::class, 'store'])->name('weeks.store');
        Route::post('weeks/bulk', [WeekController::class, 'bulkStore'])->name('weeks.bulk-store');
        Route::post('weeks/bulk-delete', [WeekController::class, 'bulkDestroy'])->name('weeks.bulk-destroy');
        Route::put('weeks/{weekId}', [WeekController::class, 'update'])->name('weeks.update');
        Route::delete('weeks/{weekId}', [WeekController::class, 'destroy'])->name('weeks.destroy');
    });

// المعايير + المجمعات — global reference data, super-admin only (#248)
Route::middleware(['auth', 'role:super-admin'])
    ->prefix('admin/qb')
    ->name('admin.qb.')
    ->group(function () {
        Route::get('standards', [StandardController::class, 'index'])->name('standards.index');
        Route::get('standards/create', [StandardController::class, 'create'])->name('standards.create');
        Route::post('standards', [StandardController::class, 'store'])->name('standards.store');
        Route::get('standards/{standardId}/edit', [StandardController::class, 'edit'])->name('standards.edit');
        Route::put('standards/{standardId}', [StandardController::class, 'update'])->name('standards.update');
        Route::delete('standards/{standardId}', [StandardController::class, 'destroy'])->name('standards.destroy');

        Route::get('compounds', [CompoundController::class, 'index'])->name('compounds.index');
        Route::get('compounds/create', [CompoundController::class, 'create'])->name('compounds.create');
        Route::post('compounds', [CompoundController::class, 'store'])->name('compounds.store');
        Route::get('compounds/{compoundId}/edit', [CompoundController::class, 'edit'])->name('compounds.edit');
        Route::put('compounds/{compoundId}', [CompoundController::class, 'update'])->name('compounds.update');
        Route::delete('compounds/{compoundId}', [CompoundController::class, 'destroy'])->name('compounds.destroy');
    });
