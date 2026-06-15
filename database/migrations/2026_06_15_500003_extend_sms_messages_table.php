<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint-9 / Trello #239 + #240 — turn sms_messages into the per-recipient log
 * row that batches/reports hang off.
 *
 * Adds batch/template/recipient linkage + segment/credit columns, and widens
 * the status enum (was queued|sent|failed) to the 9 states the reports card
 * (#240) requires. We convert the enum column to a VARCHAR to avoid future
 * enum-alter churn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')->nullable()->after('school_id');
            $table->unsignedBigInteger('template_id')->nullable()->after('batch_id');
            $table->unsignedBigInteger('recipient_user_id')->nullable()->after('sender_id');
            $table->string('recipient_name', 191)->nullable()->after('recipient');
            $table->string('recipient_role', 40)->nullable()->after('recipient_name');
            $table->string('channel', 20)->default('sms')->after('provider'); // future-proof for union reports
            $table->unsignedSmallInteger('message_count')->default(1)->after('channel'); // segments
            $table->unsignedInteger('credit_charged')->default(0)->after('message_count');
            $table->unsignedBigInteger('triggered_by')->nullable()->after('credit_charged');
        });

        // Convert the status enum → string so we can store the full 9-state set
        // without an enum migration each time. (#240)
        // queued | sent | failed | invalid_number | no_number | no_credit | rejected | delivered | read
        DB::statement("ALTER TABLE sms_messages MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'queued'");

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->index('batch_id');
            $table->index(['school_id', 'channel', 'status']);
            $table->foreign('batch_id')->references('id')->on('sms_batches')->cascadeOnDelete();
            $table->foreign('template_id')->references('id')->on('sms_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropForeign(['template_id']);
            $table->dropIndex(['batch_id']);
            $table->dropIndex(['school_id', 'channel', 'status']);
            $table->dropColumn([
                'batch_id', 'template_id', 'recipient_user_id', 'recipient_name',
                'recipient_role', 'channel', 'message_count', 'credit_charged', 'triggered_by',
            ]);
        });

        DB::statement("ALTER TABLE sms_messages MODIFY COLUMN status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued'");
    }
};
