<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('event_type', 20)->default('other');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->tinyInteger('all_day')->default(1);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('color', 20)->nullable();
            $table->json('audience')->nullable();
            $table->string('location', 160)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_events');
    }
};
