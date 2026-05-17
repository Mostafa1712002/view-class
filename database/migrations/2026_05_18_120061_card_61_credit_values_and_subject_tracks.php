<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add the "actual approved" toggle to subjects (column 4 of the credit-values table).
        // We reuse the existing `credit_hours` tinyint as "weekly lessons" semantically.
        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'credit_hours_active')) {
                $table->boolean('credit_hours_active')->default(true)->after('credit_hours');
            }
        });

        // Subject tracks (شعب المواد) — academic tracks/streams like علمي/أدبي/عام/تحفيظ/دولي.
        // Freestanding lookup; subjects.section (varchar) can be populated from this list.
        if (! Schema::hasTable('subject_tracks')) {
            Schema::create('subject_tracks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');                       // Arabic
                $table->string('name_en')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['school_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_tracks');

        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'credit_hours_active')) {
                $table->dropColumn('credit_hours_active');
            }
        });
    }
};
