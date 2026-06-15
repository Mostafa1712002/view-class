<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QB rebuild foundation (#248 taxonomy): educational compounds (المجمعات) and the
 * compound ↔ school link. Additive — does not touch existing school/company tables.
 *
 * Judgment call: `educational_companies` already exists (billing/company concept) and
 * `school_branches` is unrelated. A dedicated educational "compound" grouping is the
 * cleanest additive model for the platform-of-Al-Awwal-style hierarchy. A later card
 * may reconcile compound ↔ educational_company if the product collapses the two.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compounds')) {
            Schema::create('compounds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('educational_company_id')->nullable()->index();
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active')->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('compound_school')) {
            Schema::create('compound_school', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('compound_id')->index();
                $table->unsignedBigInteger('school_id')->index();
                $table->timestamps();
                $table->unique(['compound_id', 'school_id'], 'compound_school_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compound_school');
        Schema::dropIfExists('compounds');
    }
};
