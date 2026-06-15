<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint-9 / Trello #241 — bulk/automatic student message settings.
 *
 * One row per (school, event_type). Each event type (late arrival, device
 * offline alert, arrival scan, departure scan, leave-without-checkout, excuse)
 * carries an enable toggle + an editable message template body.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_auto_message_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('event_type', 60); // see SmsAutoMessageSetting::EVENT_TYPES
            $table->boolean('is_enabled')->default(false);
            $table->text('template_body')->nullable();
            // optional numeric threshold (e.g. "late more than N days")
            $table->unsignedInteger('threshold')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_auto_message_settings');
    }
};
