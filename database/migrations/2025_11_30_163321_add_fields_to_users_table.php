<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->foreignId('section_id')->nullable()->after('school_id')->constrained()->onDelete('set null');
            $table->string('employee_id')->nullable()->after('section_id'); // رقم الموظف
            $table->string('national_id')->nullable()->after('employee_id'); // رقم الهوية
            $table->string('phone')->nullable()->after('national_id');
            $table->string('phone_secondary')->nullable()->after('phone');
            $table->text('address')->nullable()->after('phone_secondary');
            $table->enum('gender', ['male', 'female'])->default('male')->after('address');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->date('hire_date')->nullable()->after('date_of_birth');
            $table->string('specialization')->nullable()->after('hire_date'); // التخصص
            $table->string('qualification')->nullable()->after('specialization'); // المؤهل
            $table->string('avatar')->nullable()->after('qualification');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['section_id']);
            $table->dropColumn([
                'school_id', 'section_id', 'employee_id', 'national_id',
                'phone', 'phone_secondary', 'address', 'gender',
                'date_of_birth', 'hire_date', 'specialization', 'qualification',
                'avatar', 'is_active', 'last_login_at'
            ]);
        });
    }
};
