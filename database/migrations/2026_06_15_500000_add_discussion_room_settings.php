<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card #235 — discussion rooms gap-fill.
 *
 * Adds the room-behaviour settings the spec form asks for (instructions,
 * allow new topics, allow comments, require approval) plus a per-topic
 * comment-toggle so staff can stop replies on a single topic.
 *
 * Additive + idempotent: safe to run on the deployed DB. All new columns
 * are nullable or sensibly defaulted so existing rows keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussion_rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('discussion_rooms', 'instructions')) {
                $table->text('instructions')->nullable()->after('description');
            }
            if (! Schema::hasColumn('discussion_rooms', 'allow_topics')) {
                $table->boolean('allow_topics')->default(true)->after('audience');
            }
            if (! Schema::hasColumn('discussion_rooms', 'allow_comments')) {
                $table->boolean('allow_comments')->default(true)->after('allow_topics');
            }
            if (! Schema::hasColumn('discussion_rooms', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('allow_comments');
            }
            if (! Schema::hasColumn('discussion_rooms', 'comments_count')) {
                $table->unsignedInteger('comments_count')->default(0)->after('topics_count');
            }
            if (! Schema::hasColumn('discussion_rooms', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('comments_count');
            }
        });

        Schema::table('discussion_topics', function (Blueprint $table) {
            // Per-topic "إيقاف التعليق" — distinct from is_closed (whole topic).
            if (! Schema::hasColumn('discussion_topics', 'comments_closed')) {
                $table->boolean('comments_closed')->default(false)->after('is_closed');
            }
            // Per-topic "إخفاء" — soft hide from members without deleting.
            if (! Schema::hasColumn('discussion_topics', 'is_hidden')) {
                $table->boolean('is_hidden')->default(false)->after('comments_closed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('discussion_rooms', function (Blueprint $table) {
            foreach (['instructions', 'allow_topics', 'allow_comments', 'requires_approval', 'comments_count', 'last_activity_at'] as $col) {
                if (Schema::hasColumn('discussion_rooms', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('discussion_topics', function (Blueprint $table) {
            foreach (['comments_closed', 'is_hidden'] as $col) {
                if (Schema::hasColumn('discussion_topics', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
