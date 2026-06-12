<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->string('creator_role', 20);
            $table->string('category', 40);
            $table->string('subject', 160);
            $table->text('body');
            $table->string('priority', 10)->default('normal');
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
