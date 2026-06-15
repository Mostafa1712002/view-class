<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint-9 / Trello #243 — widen the sms_senders status workflow.
 *
 * The original enum (pending|approved|rejected) is too small for the 7 states
 * the card requires: draft|submitted|under_review|needs_edit|accepted|rejected|active.
 * We also add a sender "kind" (alerts vs advertising — different length limits)
 * and keep the legacy values mapped to the new vocabulary.
 */
return new class extends Migration
{
    public function up(): void
    {
        // status enum → varchar (7 states)
        DB::statement("ALTER TABLE sms_senders MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'draft'");

        // Map legacy values onto the new workflow.
        DB::table('sms_senders')->where('status', 'pending')->update(['status' => 'submitted']);
        DB::table('sms_senders')->where('status', 'approved')->update(['status' => 'active']);

        Schema::table('sms_senders', function (Blueprint $table) {
            // alerts (<=11 chars) | advertising (<=8 chars)
            $table->string('kind', 20)->default('alerts')->after('name_en');
        });
    }

    public function down(): void
    {
        Schema::table('sms_senders', function (Blueprint $table) {
            $table->dropColumn('kind');
        });

        DB::table('sms_senders')->where('status', 'submitted')->update(['status' => 'pending']);
        DB::table('sms_senders')->whereIn('status', ['active', 'accepted'])->update(['status' => 'approved']);
        DB::table('sms_senders')->whereIn('status', ['draft', 'under_review', 'needs_edit'])->update(['status' => 'pending']);

        DB::statement("ALTER TABLE sms_senders MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
    }
};
