<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card #235 — let a discussion room be aimed at a specific audience and carry a
 * subject + category, mirroring the virtual-classes targeting model (#234):
 *   - subject_id : optional المادة link.
 *   - category   : free-text التصنيف/التحضير label.
 *   - target_type: all | students | teachers | parents | admins
 *                  | specific_users | specific_roles | job_titles
 *   - grade_levels / class_ids: JSON narrowing for the `students` audience.
 *   - discussion_room_targets: per-room user/role/job_title pivot rows.
 *
 * Additive + idempotent. Existing rows keep target_type='all' (the column
 * default), so every current room stays visible to everyone — no backfill needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussion_rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('discussion_rooms', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('school_id');
            }
            if (! Schema::hasColumn('discussion_rooms', 'category')) {
                $table->string('category', 100)->nullable()->after('instructions');
            }
            if (! Schema::hasColumn('discussion_rooms', 'target_type')) {
                $table->string('target_type', 20)->default('all')->after('audience');
            }
            if (! Schema::hasColumn('discussion_rooms', 'grade_levels')) {
                $table->json('grade_levels')->nullable()->after('target_type');
            }
            if (! Schema::hasColumn('discussion_rooms', 'class_ids')) {
                $table->json('class_ids')->nullable()->after('grade_levels');
            }
        });

        if (! Schema::hasTable('discussion_room_targets')) {
            Schema::create('discussion_room_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discussion_room_id')->constrained('discussion_rooms')->cascadeOnDelete();
                // kind: user | role | job_title
                $table->enum('kind', ['user', 'role', 'job_title']);
                // user -> users.id ; role -> roles.id ; job_title -> job_titles.id
                $table->unsignedBigInteger('target_id');
                $table->timestamps();

                $table->index(['discussion_room_id', 'kind']);
                $table->unique(['discussion_room_id', 'kind', 'target_id'], 'discussion_room_target_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_room_targets');

        Schema::table('discussion_rooms', function (Blueprint $table) {
            foreach (['subject_id', 'category', 'target_type', 'grade_levels', 'class_ids'] as $c) {
                if (Schema::hasColumn('discussion_rooms', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
