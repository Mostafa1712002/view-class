<?php

use App\Modules\SmsServices\Controllers\CreditController;
use App\Modules\SmsServices\Controllers\SenderNameController;
use App\Modules\SmsServices\Controllers\SmsAutoMessageController;
use App\Modules\SmsServices\Controllers\SmsExcelController;
use App\Modules\SmsServices\Controllers\SmsReportsController;
use App\Modules\SmsServices\Controllers\SmsSendController;
use App\Modules\SmsServices\Controllers\SmsTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SMS Messaging — Sprint 9 (Trello #238 #239 #240 #241 #243)
|--------------------------------------------------------------------------
| Tenant-facing SMS surface (scopedSchoolId). Distinct prefix/name (admin.sms.*)
| from the legacy super-admin per-school block (admin.sms-services.*) in
| routes/web.php to avoid route-name collisions. Every action is canDo-gated in
| the controller; routes also carry the permission middleware as a first gate.
*/

Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/sms')
    ->name('admin.sms.')
    ->group(function () {

        // --- Templates (#238) ---
        Route::middleware('permission:messages.templates')->group(function () {
            Route::get('templates', [SmsTemplateController::class, 'index'])->name('templates.index');
            Route::get('templates/create', [SmsTemplateController::class, 'create'])->name('templates.create');
            Route::post('templates', [SmsTemplateController::class, 'store'])->name('templates.store');
            Route::get('templates/{id}/edit', [SmsTemplateController::class, 'edit'])->whereNumber('id')->name('templates.edit');
            Route::put('templates/{id}', [SmsTemplateController::class, 'update'])->whereNumber('id')->name('templates.update');
            Route::delete('templates/{id}', [SmsTemplateController::class, 'destroy'])->whereNumber('id')->name('templates.destroy');
            Route::post('templates/{id}/copy', [SmsTemplateController::class, 'copy'])->whereNumber('id')->name('templates.copy');
            Route::post('templates/{id}/toggle', [SmsTemplateController::class, 'toggle'])->whereNumber('id')->name('templates.toggle');
            Route::post('templates/{id}/try', [SmsTemplateController::class, 'tryTemplate'])->whereNumber('id')->name('templates.try');
            Route::post('templates/analyze', [SmsTemplateController::class, 'analyze'])->name('templates.analyze');

            // --- Auto-message settings / message models (#241) ---
            Route::get('auto-messages', [SmsAutoMessageController::class, 'index'])->name('auto-messages.index');
            Route::get('auto-messages/{type}/edit', [SmsAutoMessageController::class, 'edit'])->name('auto-messages.edit');
            Route::put('auto-messages/{type}', [SmsAutoMessageController::class, 'update'])->name('auto-messages.update');
            Route::post('auto-messages/{type}/toggle', [SmsAutoMessageController::class, 'toggle'])->name('auto-messages.toggle');
        });

        // --- Send SMS (#239) ---
        Route::middleware('permission:sms.send')->group(function () {
            Route::get('send', [SmsSendController::class, 'create'])->name('send');
            Route::post('send', [SmsSendController::class, 'store'])->name('send.store');
            Route::post('send/recipients', [SmsSendController::class, 'resolveRecipients'])->name('send.recipients');
            Route::get('send/search', [SmsSendController::class, 'searchUsers'])->name('send.search');
            Route::post('send/preview', [SmsSendController::class, 'preview'])->name('send.preview');
        });

        // --- Send from Excel (#239) ---
        Route::middleware('permission:messages.send_excel')->group(function () {
            Route::get('excel', [SmsExcelController::class, 'create'])->name('excel.create');
            Route::get('excel/template', [SmsExcelController::class, 'template'])->name('excel.template');
            Route::post('excel/preview', [SmsExcelController::class, 'preview'])->name('excel.preview');
            Route::post('excel/send', [SmsExcelController::class, 'send'])->name('excel.send');
            Route::post('excel/clear', [SmsExcelController::class, 'clear'])->name('excel.clear');
        });

        // --- Reports / logs (#240) ---
        Route::middleware('permission:messages.reports')->group(function () {
            Route::get('reports', [SmsReportsController::class, 'index'])->name('reports.index');
            Route::get('reports/export/excel', [SmsReportsController::class, 'exportExcel'])->name('reports.export.excel');
            Route::get('reports/export/pdf', [SmsReportsController::class, 'exportPdf'])->name('reports.export.pdf');
            Route::post('reports/{id}/resend', [SmsReportsController::class, 'resend'])->whereNumber('id')->name('reports.resend');
        });

        // --- Sender name request (#243) ---
        Route::middleware('permission:messages.sender_name')->group(function () {
            Route::get('sender-name', [SenderNameController::class, 'index'])->name('sender-name.index');
            Route::get('sender-name/create', [SenderNameController::class, 'create'])->name('sender-name.create');
            Route::post('sender-name', [SenderNameController::class, 'store'])->name('sender-name.store');
            Route::delete('sender-name/{id}', [SenderNameController::class, 'destroy'])->whereNumber('id')->name('sender-name.destroy');
            // super-admin review (enforced in controller)
            Route::post('sender-name/{id}/approve', [SenderNameController::class, 'approve'])->whereNumber('id')->name('sender-name.approve');
            Route::post('sender-name/{id}/reject', [SenderNameController::class, 'reject'])->whereNumber('id')->name('sender-name.reject');
        });

        // --- Credit recharge (#243) ---
        Route::middleware('permission:messages.credit')->group(function () {
            Route::get('credit', [CreditController::class, 'index'])->name('credit.index');
            Route::post('credit', [CreditController::class, 'store'])->name('credit.store');
            Route::post('credit/{id}/approve', [CreditController::class, 'approve'])->whereNumber('id')->name('credit.approve');
            Route::post('credit/{id}/reject', [CreditController::class, 'reject'])->whereNumber('id')->name('credit.reject');
        });
    });
