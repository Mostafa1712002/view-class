<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_mail_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mail_id')->index();
            $table->unsignedBigInteger('recipient_id')->index();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('starred')->default(false);
            $table->boolean('is_task')->default(false);
            $table->boolean('archived')->default(false);
            $table->boolean('trashed')->default(false);
            $table->timestamps();

            $table->foreign('mail_id')->references('id')->on('internal_mails')->cascadeOnDelete();
            $table->foreign('recipient_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['recipient_id', 'trashed', 'archived']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_mail_recipients');
    }
};
