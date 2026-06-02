<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cards #104 + #105 — سياسات التعليم: organisational policy documents targeted
 * at roles, with per-user acknowledgement tracking + notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('target_roles'); // ['student','teacher','parent','school-admin']
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('policy_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->index();
            $table->foreignId('user_id')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['policy_id', 'user_id']); // one row per user per policy
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_acknowledgements');
        Schema::dropIfExists('policies');
    }
};
