<?php

use App\Modules\Certificates\Controllers\AdminCertificateController;
use App\Modules\Certificates\Controllers\CertificateTemplateController;
use App\Modules\Certificates\Controllers\MyCertificateController;
use App\Modules\Certificates\Controllers\PublicCertificateController;
use Illuminate\Support\Facades\Route;

/*
| Certificates module routes (Trello #266).
| Required from routes/web.php. Migrated from role:-based gating to permission:
| middleware (slugs seeded under the `certificates` group).
*/

Route::middleware(['auth'])->group(function () {

    // === Certificate design templates ===
    Route::prefix('admin/certificate-templates')
        ->name('admin.certificate-templates.')
        ->middleware('role:super-admin,school-admin')
        ->group(function () {
            Route::get('/', [CertificateTemplateController::class, 'index'])
                ->middleware('permission:certificates.view')->name('index');
            Route::get('/create', [CertificateTemplateController::class, 'create'])
                ->middleware('permission:certificates.template_create')->name('create');
            Route::post('/', [CertificateTemplateController::class, 'store'])
                ->middleware('permission:certificates.template_create')->name('store');
            Route::get('/{template}/edit', [CertificateTemplateController::class, 'edit'])
                ->middleware('permission:certificates.template_edit')->name('edit');
            Route::put('/{template}', [CertificateTemplateController::class, 'update'])
                ->middleware('permission:certificates.template_edit')->name('update');
            Route::delete('/{template}', [CertificateTemplateController::class, 'destroy'])
                ->middleware('permission:certificates.template_delete')->name('destroy');
        });

    // === Certificates (admin) ===
    Route::prefix('admin/certificates')
        ->name('admin.certificates.')
        ->middleware('role:super-admin,school-admin')
        ->group(function () {
            Route::get('/', [AdminCertificateController::class, 'index'])
                ->middleware('permission:certificates.view')->name('index');

            // Template-based issuing (single + bulk).
            Route::get('/issue', [AdminCertificateController::class, 'issueForm'])
                ->middleware('permission:certificates.issue')->name('issue.form');
            Route::post('/issue', [AdminCertificateController::class, 'issue'])
                ->middleware('permission:certificates.issue')->name('issue');

            // Legacy single file-upload create/edit (kept for backward compat).
            Route::get('/create', [AdminCertificateController::class, 'create'])
                ->middleware('permission:certificates.create')->name('create');
            Route::post('/', [AdminCertificateController::class, 'store'])
                ->middleware('permission:certificates.create')->name('store');
            Route::get('/{certificate}/edit', [AdminCertificateController::class, 'edit'])
                ->middleware('permission:certificates.edit')->name('edit');
            Route::put('/{certificate}', [AdminCertificateController::class, 'update'])
                ->middleware('permission:certificates.edit')->name('update');
            Route::post('/{certificate}/publish', [AdminCertificateController::class, 'publish'])
                ->middleware('permission:certificates.edit')->name('publish');
            Route::delete('/{certificate}', [AdminCertificateController::class, 'destroy'])
                ->middleware('permission:certificates.delete')->name('destroy');

            // Preview / PDF / send.
            Route::get('/{certificate}/preview', [AdminCertificateController::class, 'preview'])
                ->middleware('permission:certificates.preview')->name('preview');
            Route::get('/{certificate}/pdf', [AdminCertificateController::class, 'pdf'])
                ->middleware('permission:certificates.preview')->name('pdf');
            Route::get('/{certificate}/send', [AdminCertificateController::class, 'sendForm'])
                ->middleware('permission:certificates.send')->name('send');
            Route::post('/{certificate}/send', [AdminCertificateController::class, 'sendStore'])
                ->middleware('permission:certificates.send')->name('send.store');
            Route::get('/{certificate}/progress', [AdminCertificateController::class, 'progress'])
                ->middleware('permission:certificates.view')->name('progress');
        });

    // === Teacher / student / parent: read-only certificates view ===
    Route::prefix('my/certificates')
        ->name('my.certificates.')
        ->group(function () {
            Route::get('/', [MyCertificateController::class, 'index'])
                ->middleware('permission:certificates.view')->name('index');
        });
});

// === Public tokenised share link (no auth) ===
Route::get('certificates/share/{token}', [PublicCertificateController::class, 'show'])
    ->name('certificates.share');
