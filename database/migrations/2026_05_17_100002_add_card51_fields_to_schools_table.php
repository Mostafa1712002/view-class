<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Trello card 51: extra fields on the schools/create form.
            if (!Schema::hasColumn('schools', 'student_gender')) {
                $table->enum('student_gender', ['boys', 'girls', 'mixed'])
                    ->default('mixed')->after('city');
            }
            if (!Schema::hasColumn('schools', 'timezone')) {
                $table->string('timezone', 64)->default('Asia/Riyadh')->after('student_gender');
            }
            if (!Schema::hasColumn('schools', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('branch')
                    ->constrained('school_branches')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
            if (Schema::hasColumn('schools', 'timezone')) {
                $table->dropColumn('timezone');
            }
            if (Schema::hasColumn('schools', 'student_gender')) {
                $table->dropColumn('student_gender');
            }
        });
    }
};
