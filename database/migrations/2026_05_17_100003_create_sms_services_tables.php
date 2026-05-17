<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_sms_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('provider', 60)->default('generic');
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sms_used')->default(0);
            $table->unsignedInteger('sms_total')->default(0);
            $table->unsignedBigInteger('default_sender_id')->nullable();
            $table->timestamps();
            $table->unique('school_id');
        });

        Schema::create('sms_senders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name_ar', 11);
            $table->string('name_en', 11);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_sender_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('sms_senders')->cascadeOnDelete();
            $table->enum('provider', ['stc', 'mobily', 'zain']);
            $table->string('file_path');
            $table->timestamps();
        });

        // FK on the soft pointer from school_sms_settings → sms_senders.
        Schema::table('school_sms_settings', function (Blueprint $table) {
            $table->foreign('default_sender_id')
                ->references('id')->on('sms_senders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('school_sms_settings', function (Blueprint $table) {
            $table->dropForeign(['default_sender_id']);
        });
        Schema::dropIfExists('sms_sender_attachments');
        Schema::dropIfExists('sms_senders');
        Schema::dropIfExists('school_sms_settings');
    }
};
