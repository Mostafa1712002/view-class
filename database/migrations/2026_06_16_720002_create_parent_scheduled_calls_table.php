<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent CRM — scheduled calls (Sprint 10, Trello #269). Additive; repo-scoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parent_scheduled_calls')) {
            return;
        }

        Schema::create('parent_scheduled_calls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('parent_id')->index();
            $table->date('call_date');
            $table->time('call_time')->nullable();
            $table->string('call_type', 20)->default('outgoing');
            $table->string('purpose', 255);
            $table->text('outcome')->nullable();
            $table->boolean('answered')->default(false);
            $table->text('notes')->nullable();
            $table->dateTime('followup_at')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('status', 30)->default('scheduled');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_scheduled_calls');
    }
};
