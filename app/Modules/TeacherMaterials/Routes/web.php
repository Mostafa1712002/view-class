<?php

use App\Modules\TeacherMaterials\Controllers\TeacherMaterialController;
use Illuminate\Support\Facades\Route;

// Teacher "إدارة المواد" hub — Trello #287.
// Teacher-accessible group (super-admin/school-admin/teacher); admin.* pages
// stay gated to admins, so teachers reach this hub but not admin management.
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('teacher/materials')
    ->name('teacher.materials.')
    ->group(function () {
        Route::get('/', [TeacherMaterialController::class, 'index'])->name('index');
        // Cascading AJAX endpoints — each validates subject ownership (403).
        Route::get('grades', [TeacherMaterialController::class, 'grades'])->name('grades');
        Route::get('classes', [TeacherMaterialController::class, 'classes'])->name('classes');
        Route::get('results', [TeacherMaterialController::class, 'results'])->name('results');
    });
