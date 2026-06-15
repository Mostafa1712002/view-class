<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card #234 — link virtual classes to attendance.
 *
 * 1. virtual_classes: add `platform` (zoom/teams/external/internal) + `started_at`
 *    (drives the "بدأ المعلم" column and the join/start window).
 * 2. virtual_class_attendees: per-student entry/exit log for a virtual class. This is
 *    the SOURCE the recalc action reads to compute duration → status. The full 4-state
 *    truth (incl. `partial`/"حضر جزئيًا") lives HERE; when mirroring into the shared
 *    `attendances` table the recalc maps `partial`→`late` so it stays within the legacy
 *    4-status vocabulary the existing parent/student reports understand — no shared
 *    enum change, no edits to report code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('virtual_classes', 'platform')) {
                $table->string('platform', 20)->default('zoom')->after('status');
            }
            if (! Schema::hasColumn('virtual_classes', 'external_url')) {
                $table->text('external_url')->nullable()->after('passcode');
            }
            if (! Schema::hasColumn('virtual_classes', 'started_at')) {
                $table->dateTime('started_at')->nullable()->after('status');
            }
        });

        if (! Schema::hasTable('virtual_class_attendees')) {
            Schema::create('virtual_class_attendees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('virtual_class_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('school_id')->index();
                $table->dateTime('joined_at')->nullable();
                $table->dateTime('left_at')->nullable();
                $table->integer('duration_minutes')->default(0);
                $table->string('attendance_status', 20)->nullable(); // computed by recalc
                $table->timestamps();

                $table->unique(['virtual_class_id', 'student_id'], 'vc_attendee_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_class_attendees');

        Schema::table('virtual_classes', function (Blueprint $table) {
            foreach (['platform', 'external_url', 'started_at'] as $c) {
                if (Schema::hasColumn('virtual_classes', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
