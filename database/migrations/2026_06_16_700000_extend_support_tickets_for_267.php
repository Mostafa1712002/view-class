<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #267 — Support / Tickets full experience.
 *
 * Additive only. Adds ticket `type` (نوع التذكرة), `department` (القسم) and
 * `problem_url` (رابط المشكلة) to support_tickets, and a status-change log table
 * (سجل تغيير الحالة). The existing `category` column is LEFT INTACT — legacy
 * tickets + lang keys depend on it; `department` is the card's new "القسم" axis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('support_tickets', 'type')) {
                $table->string('type', 40)->nullable()->after('creator_role');
            }
            if (! Schema::hasColumn('support_tickets', 'department')) {
                $table->string('department', 40)->nullable()->after('category');
            }
            if (! Schema::hasColumn('support_tickets', 'problem_url')) {
                $table->string('problem_url', 500)->nullable()->after('body');
            }
        });

        if (! Schema::hasTable('support_ticket_status_logs')) {
            Schema::create('support_ticket_status_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('from_status', 20)->nullable();
                $table->string('to_status', 20);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_status_logs');

        Schema::table('support_tickets', function (Blueprint $table) {
            foreach (['type', 'department', 'problem_url'] as $col) {
                if (Schema::hasColumn('support_tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
