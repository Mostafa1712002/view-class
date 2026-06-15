<?php

use App\Modules\QuestionBankCore\Controllers\QuestionController;
use App\Modules\QuestionBankCore\Controllers\ScopeSelectorController;
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
    });
