<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot table linking job titles to granular permissions.
 * scope: defines data visibility for this permission on this job title.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_title_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->enum('scope', [
                'all',          // كل النظام
                'company',      // الشركة
                'group',        // المجمع
                'school',       // المدرسة
                'stage',        // المرحلة
                'class',        // الصف
                'section',      // الفصل
                'subject',      // المادة
                'own_students', // الطلاب المرتبطون فقط
                'own_subjects', // المواد المسندة فقط
                'own',          // بياناته فقط
            ])->default('school');
            $table->timestamps();

            $table->unique(['job_title_id', 'permission_id'], 'jtp_unique');
            $table->index(['job_title_id'], 'jtp_job_title_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_title_permissions');
    }
};
