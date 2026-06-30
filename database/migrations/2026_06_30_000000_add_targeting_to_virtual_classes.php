<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Card #234 — link virtual classes to a target audience so they surface on the
 * student/parent /my/virtual-classes page.
 *
 * Mirrors the announcements / school-calendar targeting model:
 *   - target_type: all | students | teachers | parents | admins
 *                  | specific_users | specific_roles | job_titles
 *   - grade_levels / class_ids: JSON narrowing for the `students` audience.
 *   - virtual_class_targets: per-session user/role/job_title pivot rows.
 *
 * Backfill keeps existing rows behaving as before: a session bound to a single
 * `class_id` becomes a `students` session targeting that class; everything else
 * becomes `all`. (`class_id` itself stays — it still feeds the attendance roster.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('virtual_classes', 'target_type')) {
                $table->string('target_type', 20)->default('all')->after('audience');
            }
            if (! Schema::hasColumn('virtual_classes', 'grade_levels')) {
                $table->json('grade_levels')->nullable()->after('target_type');
            }
            if (! Schema::hasColumn('virtual_classes', 'class_ids')) {
                $table->json('class_ids')->nullable()->after('grade_levels');
            }
        });

        if (! Schema::hasTable('virtual_class_targets')) {
            Schema::create('virtual_class_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('virtual_class_id')->constrained('virtual_classes')->cascadeOnDelete();
                // kind: user | role | job_title
                $table->enum('kind', ['user', 'role', 'job_title']);
                // user -> users.id ; role -> roles.id ; job_title -> job_titles.id
                $table->unsignedBigInteger('target_id');
                $table->timestamps();

                $table->index(['virtual_class_id', 'kind']);
                $table->unique(['virtual_class_id', 'kind', 'target_id'], 'virtual_class_target_unique');
            });
        }

        // Backfill existing rows: class-bound sessions target that class's students.
        DB::table('virtual_classes')
            ->whereNotNull('class_id')
            ->update([
                'target_type' => 'students',
                'class_ids'   => DB::raw('JSON_ARRAY(class_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_class_targets');

        Schema::table('virtual_classes', function (Blueprint $table) {
            foreach (['target_type', 'grade_levels', 'class_ids'] as $c) {
                if (Schema::hasColumn('virtual_classes', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
