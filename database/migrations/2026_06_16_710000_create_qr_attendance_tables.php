<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attendance groups define the time windows that turn a scan into a status.
        Schema::create('qr_attendance_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->enum('default_status', ['present', 'late', 'absent', 'excused'])->default('present');
            $table->time('present_start')->nullable();
            $table->time('late_start')->nullable();
            $table->time('absent_start')->nullable();
            $table->time('excuse_start')->nullable();
            // working days as JSON array of 0..6 (0=Sunday)
            $table->json('work_days')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_id', 'is_active']);
        });

        // QR cards: secure token (never raw id) per student.
        Schema::create('qr_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('qr_attendance_groups')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('card_code', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_id', 'is_active']);
        });

        // Scan log + day-close marker store.
        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_card_id')->nullable()->constrained('qr_cards')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('qr_attendance_groups')->nullOnDelete();
            $table->date('scan_date');
            $table->dateTime('scanned_at');
            $table->enum('result_status', ['present', 'late', 'absent', 'excused', 'rejected'])->default('present');
            $table->string('channel', 32)->default('camera'); // camera | manual | iot
            $table->string('device_name')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'scan_date']);
            $table->index('student_id');
        });

        // Day-close markers (which class/section/school days are locked).
        Schema::create('qr_day_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->date('close_date');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'class_id', 'close_date'], 'qr_day_closure_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_day_closures');
        Schema::dropIfExists('qr_scans');
        Schema::dropIfExists('qr_cards');
        Schema::dropIfExists('qr_attendance_groups');
    }
};
