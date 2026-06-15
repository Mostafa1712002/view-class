<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #270 — Educational websites (NET-NEW). A directory of external educational
 * site links rendered as cards for end-users, managed from the admin panel.
 *
 * `school_id` is NULLABLE on purpose:
 *   - NULL  → a GLOBAL site (e.g. Turnitin, Snapplify) visible to every school;
 *             created by a super-admin operating without an active school scope.
 *   - <int> → a school-owned site, visible only inside that school.
 * Display query: is_active = 1 AND (school_id IS NULL OR school_id = viewer's school).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educational_sites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('name_ar')->nullable();
            $table->string('name_en');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('url');
            $table->string('logo_path')->nullable();
            $table->string('category')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('opens_new_tab')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('educational_sites');
    }
};
