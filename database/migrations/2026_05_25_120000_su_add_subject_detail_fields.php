<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card "ادارة المواد" — extra subject detail fields requested by the spec:
 *   - short names (ar/en) for compact use in tables/certificates
 *   - language of instruction
 *   - total academic hours (عدد الساعات) — distinct from weekly lessons
 *   - credit value (القيمة المعتمدة) — distinct from credit_hours which is
 *     semantically reused as "weekly lessons" (see card_61 migration)
 *   - icon (LineAwesome class) for the appearance section.
 *
 * Idempotent: each column is guarded so re-deploy is safe.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'short_name_ar')) {
                $table->string('short_name_ar')->nullable()->after('name_en');
            }
            if (! Schema::hasColumn('subjects', 'short_name_en')) {
                $table->string('short_name_en')->nullable()->after('short_name_ar');
            }
            if (! Schema::hasColumn('subjects', 'language')) {
                $table->string('language', 10)->nullable()->after('short_name_en');
            }
            if (! Schema::hasColumn('subjects', 'total_hours')) {
                $table->unsignedTinyInteger('total_hours')->nullable()->after('credit_hours_active');
            }
            if (! Schema::hasColumn('subjects', 'credit_value')) {
                $table->unsignedTinyInteger('credit_value')->nullable()->after('total_hours');
            }
            if (! Schema::hasColumn('subjects', 'icon')) {
                $table->string('icon')->nullable()->after('credit_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            foreach (['short_name_ar', 'short_name_en', 'language', 'total_hours', 'credit_value', 'icon'] as $col) {
                if (Schema::hasColumn('subjects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
