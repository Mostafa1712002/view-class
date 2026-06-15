<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_broadcasts', function (Blueprint $table) {
            $table->id();
            // nullable so a super-admin acting with no active school (all schools) can still broadcast
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();

            // text | image | pdf
            $table->string('message_type')->default('text');
            $table->text('body')->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_original_name')->nullable();

            // human label for the recipient group chosen (e.g. "كل الطلاب")
            $table->string('audience_label')->nullable();

            // aggregate counters captured at send time
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);

            $table->string('provider')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_broadcasts');
    }
};
