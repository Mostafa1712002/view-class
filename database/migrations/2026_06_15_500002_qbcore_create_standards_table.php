<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QB rebuild foundation (#258): educational standards (المعايير). `domains` already
 * exists (subject-scoped); standards sit under a domain/subject. New table — no
 * `standards` table existed before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('standards')) {
            return;
        }

        Schema::create('standards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->unsignedBigInteger('domain_id')->nullable()->index();
            $table->string('code', 60)->nullable()->index();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standards');
    }
};
