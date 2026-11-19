<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Overrides of the code catalog. school_id 0 = platform row (NOT NULL so the unique key holds on MySQL).
        Schema::create('point_rule_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->default(0);
            $table->string('rule_key', 64);
            $table->integer('points')->nullable();
            $table->integer('period_points')->nullable();
            $table->boolean('is_enabled')->nullable();
            $table->boolean('allow_school_override')->default(false);
            $table->json('options')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'rule_key']);
        });

        // Insert-only ledger: no updated_at, no soft delete.
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->string('rule_key', 64);
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->unsignedSmallInteger('revision');
            $table->integer('points');
            $table->string('kind', 10);
            $table->string('reason', 255)->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'rule_key', 'source_type', 'source_id', 'revision'], 'point_tx_key_revision_unique');
            $table->index(['school_id', 'user_id', 'points'], 'point_tx_balance_idx');
            $table->index(['user_id', 'created_at'], 'point_tx_history_idx');
            $table->index(['school_id', 'created_at'], 'point_tx_school_time_idx');
            $table->index(['source_type', 'source_id'], 'point_tx_source_idx');
        });

        Schema::create('point_reward_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedInteger('min_points');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_id', 'starts_on', 'ends_on'], 'point_periods_school_dates_idx');
        });

        Schema::create('point_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('period_id');
            $table->integer('points_at_award');
            $table->string('status', 10)->default('earned');
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();
            $table->timestamps();
            $table->unique(['period_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_rewards');
        Schema::dropIfExists('point_reward_periods');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('point_rule_settings');
    }
};
