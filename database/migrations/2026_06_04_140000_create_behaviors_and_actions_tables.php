<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('behaviors')) {
            Schema::create('behaviors', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('behavior_group_id')->index();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('behavior_actions')) {
            Schema::create('behavior_actions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('behavior_id')->index();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->text('description');
                $table->integer('points')->default(0);
                $table->enum('point_type', ['add', 'deduct'])->default('add'); // إضافة / خصم
                $table->boolean('notify_parent')->default(false);              // إشعار ولي الأمر
                $table->boolean('needs_followup')->default(false);             // يحتاج متابعة
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_actions');
        Schema::dropIfExists('behaviors');
    }
};
