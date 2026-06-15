<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint-9 / Trello #239 — SMS send batch (one row per compose / Excel send).
 *
 * Mirrors whatsapp_broadcasts: aggregates the recipients of a single send so
 * reports can group + show success/fail counts and the total credit charged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_user_id')->nullable(); // the staff member who sent
            $table->unsignedBigInteger('sender_id')->nullable();      // sms_senders row (sender name)
            $table->string('sender_name_snapshot', 32)->nullable();   // resolved name at send time
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('name', 191)->nullable();                  // operation label
            $table->string('source', 20)->default('compose');         // compose | excel
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('total_messages')->default(0);    // total segments across recipients
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('queued_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('credit_charged')->default(0);
            $table->string('provider', 60)->nullable();
            $table->string('status', 20)->default('queued');          // queued | partial | sent | failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'created_at']);
            $table->foreign('sender_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sender_id')->references('id')->on('sms_senders')->nullOnDelete();
            $table->foreign('template_id')->references('id')->on('sms_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_batches');
    }
};
