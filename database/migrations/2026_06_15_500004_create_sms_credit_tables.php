<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint-9 / Trello #243 — SMS credit ledger + recharge requests.
 *
 * - sms_credit_ledger: immutable audit of every credit movement (school,
 *   type, balance before/after, amount, reason, user). Source of truth for
 *   "how much credit was deducted" in reports.
 * - sms_credit_recharge_requests: bank-transfer recharge requests that only
 *   add credit once an admin approves them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_credit_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('type', 20); // recharge | deduction | adjustment | refund
            $table->integer('balance_before')->default(0);
            $table->integer('amount'); // signed: + recharge, - deduction
            $table->integer('balance_after')->default(0);
            $table->string('reason', 191)->nullable();
            $table->nullableMorphs('reference'); // e.g. sms_batches / recharge request
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'created_at']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('sms_credit_recharge_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('bank_name', 120)->nullable();          // selected destination bank
            $table->decimal('amount_transferred', 12, 2);
            $table->date('transfer_date')->nullable();
            $table->string('from_bank', 120)->nullable();
            $table->string('from_account_no', 60)->nullable();
            $table->string('from_account_name', 120)->nullable();
            $table->string('receipt_path')->nullable();
            // requested credit (messages) the admin will grant on approval; defaults to 0 until set
            $table->unsignedInteger('granted_credit')->default(0);
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_credit_recharge_requests');
        Schema::dropIfExists('sms_credit_ledger');
    }
};
