<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card #233 QA bounce — the school-calendar event form was missing the
 * targeting controls (whole-school vs specific users / grades / classes) and
 * the notification + reminder toggles. This adds the backing columns and the
 * per-event target pivot (mirrors announcement_targets).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('school_events', function (Blueprint $table) {
            // 'school' = whole school (narrowed by audience roles)
            // 'specific' = chosen grades / classes / users
            $table->string('target_type', 20)->default('school')->after('audience');
            $table->json('grade_levels')->nullable()->after('target_type');
            $table->json('class_ids')->nullable()->after('grade_levels');
            $table->boolean('notify')->default(false)->after('class_ids');
            $table->boolean('remind_before')->default(false)->after('notify');
            $table->unsignedSmallInteger('remind_minutes')->nullable()->after('remind_before');
            $table->timestamp('reminded_at')->nullable()->after('remind_minutes');
        });

        Schema::create('school_event_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_event_id')->constrained('school_events')->cascadeOnDelete();
            // kind: user | role
            $table->enum('kind', ['user', 'role']);
            // For kind=user -> users.id ; for kind=role -> roles.id
            $table->unsignedBigInteger('target_id');
            $table->timestamps();

            $table->index(['school_event_id', 'kind']);
            $table->unique(['school_event_id', 'kind', 'target_id'], 'school_event_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_event_targets');

        Schema::table('school_events', function (Blueprint $table) {
            $table->dropColumn([
                'target_type', 'grade_levels', 'class_ids',
                'notify', 'remind_before', 'remind_minutes', 'reminded_at',
            ]);
        });
    }
};
