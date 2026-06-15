<?php

use App\Modules\QuestionBankCore\Controllers\PassageController;
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
    });
