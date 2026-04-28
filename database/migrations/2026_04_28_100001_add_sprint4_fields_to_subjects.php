<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->string('section')->nullable()->after('grade_levels');
            $table->unsignedTinyInteger('credit_hours')->nullable()->after('section');
            $table->unsignedSmallInteger('certificate_order')->default(0)->after('credit_hours');
            $table->enum('source', ['manual', 'excel', 'viewclass'])->default('manual')->after('certificate_order');
            $table->foreignId('template_subject_id')->nullable()->after('source')
                ->constrained('subjects')->nullOnDelete();
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['template_subject_id']);
            $table->dropColumn([
                'name_en',
                'section',
                'credit_hours',
                'certificate_order',
                'source',
                'template_subject_id',
                'deleted_at',
            ]);
        });
    }
};
