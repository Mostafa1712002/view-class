<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();

            // Tenant + author
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Content
            $table->string('title');
            $table->longText('body')->nullable();

            // نوع: عادي / مهم / منبثق
            $table->enum('type', ['normal', 'important', 'popup'])->default('normal');

            // الفئة المستهدفة: all / students / teachers / parents / admins / specific_users / specific_roles
            $table->enum('target_type', [
                'all', 'students', 'teachers', 'parents', 'admins', 'specific_users', 'specific_roles',
            ])->default('all');

            // Optional narrowing when target = students
            $table->json('grade_levels')->nullable();   // array of grade_level ints
            $table->json('class_ids')->nullable();       // array of class room ids
            $table->json('subject_ids')->nullable();     // array of subject ids (subject-linked)

            // Base status: draft / published / stopped. (scheduled/active/expired are DERIVED)
            $table->enum('status', ['draft', 'published', 'stopped'])->default('draft');

            // Display window
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Behaviour toggles
            $table->boolean('show_on_login')->default(false);
            $table->boolean('require_read_ack')->default(false);
            $table->boolean('notify_internal')->default(false);
            $table->boolean('notify_sms')->default(false);
            $table->boolean('notify_whatsapp')->default(false);

            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
