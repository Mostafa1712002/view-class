<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->text('objectives')->nullable(); // أهداف الأسبوع
            $table->text('topics')->nullable(); // المواضيع
            $table->text('activities')->nullable(); // الأنشطة
            $table->text('resources')->nullable(); // الموارد والوسائل
            $table->text('assessment')->nullable(); // التقييم
            $table->text('homework')->nullable(); // الواجبات
            $table->text('notes')->nullable(); // ملاحظات
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['teacher_id', 'subject_id', 'class_id', 'week_start_date'], 'weekly_plans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_plans');
    }
};
