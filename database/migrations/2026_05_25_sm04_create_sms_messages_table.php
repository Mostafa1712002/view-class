<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('recipient', 20);
            $table->text('body');
            // queued: handed to the (stubbed) gateway · sent: gateway accepted · failed: gateway rejected
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->string('provider', 60)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('sender_id')->references('id')->on('sms_senders')->nullOnDelete();
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
