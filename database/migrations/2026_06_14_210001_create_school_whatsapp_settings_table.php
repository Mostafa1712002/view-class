<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('whatsapp_number')->nullable();
            $table->string('provider')->default('log');
            $table->text('api_token')->nullable();
            $table->string('api_url')->nullable();
            $table->boolean('is_enabled')->default(false);

            // Toggle per event type
            $table->boolean('send_on_day_absence')->default(true);
            $table->boolean('send_on_period_absence')->default(true);
            $table->boolean('send_on_late')->default(true);
            $table->boolean('send_on_edit')->default(false);
            $table->boolean('send_on_excuse_accepted')->default(true);
            $table->boolean('send_on_excuse_rejected')->default(true);

            // Message templates (Arabic defaults applied in service)
            $table->text('template_absence')->nullable();
            $table->text('template_late')->nullable();
            $table->text('template_excuse_accepted')->nullable();
            $table->text('template_excuse_rejected')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_whatsapp_settings');
    }
};
