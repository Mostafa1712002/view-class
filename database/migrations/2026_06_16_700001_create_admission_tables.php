<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admissions / Registration module (Sprint 10 — Trello #268) — NET-NEW.
 *
 * Four module-owned, additive tables. All are tenant-scoped by `school_id`
 * (filtered in the repository, never crossing a school boundary) and default to
 * soft-delete where rows are user-managed. No existing table is altered, so this
 * migration is fully additive and safe to roll back.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Admission applications (the registration requests) ──────────────
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();                 // كود الطلب
            $table->unsignedBigInteger('school_id')->index();      // owning / requested school
            $table->unsignedBigInteger('educational_company_id')->nullable()->index();
            $table->unsignedBigInteger('academic_year_id')->nullable()->index();

            // Explicit columns rendered by the admin table / used for filtering.
            $table->string('student_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('national_id', 32)->nullable();         // الهوية
            $table->string('hijri_code', 32)->nullable();          // كود هجري
            $table->date('birth_date')->nullable();                // تاريخ الميلاد (ميلادي)
            $table->string('city')->nullable();
            $table->string('track')->nullable();                   // المسار الدراسي
            $table->string('stage')->nullable();                   // المرحلة الدراسية
            $table->string('grade')->nullable();                   // الصف الدراسي
            $table->string('nationality')->nullable();
            $table->text('address')->nullable();
            $table->dateTime('appointment_at')->nullable();        // الموعد

            // Dynamic field values + documents + notes captured from the form.
            $table->json('data')->nullable();

            $table->enum('status', [
                'new', 'under_review', 'preliminary', 'waiting',
                'scheduled', 'accepted', 'rejected', 'completed', 'cancelled',
            ])->default('new')->index();
            $table->text('status_note')->nullable();

            $table->unsignedBigInteger('converted_student_id')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->ipAddress('submitted_ip')->nullable();         // basic anti-spam audit

            $table->timestamps();
            $table->softDeletes();
        });

        // ── Per-school form field configuration ─────────────────────────────
        Schema::create('admission_form_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('field_key', 64);                       // e.g. father_name, email
            $table->string('label');                               // Arabic display label
            $table->boolean('is_visible')->default(true);          // عرض طلب
            $table->boolean('is_required')->default(false);        // مطلوب إجباري
            $table->unsignedInteger('sort_order')->default(0);     // الترتيب
            $table->timestamps();

            $table->unique(['school_id', 'field_key']);
        });

        // ── Registration-info sections (shown on the external page) ─────────
        Schema::create('admission_info_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title');                               // اسم القسم
            $table->longText('content')->nullable();               // محتوى محرر RTL
            $table->unsignedInteger('sort_order')->default(0);     // ترتيب القسم
            $table->boolean('is_active')->default(true);           // إظهار / إخفاء
            $table->timestamps();
        });

        // ── Per-school registration settings ────────────────────────────────
        Schema::create('admission_school_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->unique();
            $table->boolean('registration_enabled')->default(true); // مدرسة متاحة بالتسجيل
            $table->string('form_title')->nullable();               // عنوان الاستمارة
            $table->string('link_token', 40)->nullable()->index();  // optional opaque link token
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_school_settings');
        Schema::dropIfExists('admission_info_sections');
        Schema::dropIfExists('admission_form_fields');
        Schema::dropIfExists('admission_applications');
    }
};
