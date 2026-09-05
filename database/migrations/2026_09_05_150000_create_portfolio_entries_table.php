<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single generic table backing "إدارة ملف أنجاز" (achievement portfolio).
     * The 15 teacher/student section labels are const arrays on the model —
     * not bespoke tables — auto-derivable sections (CV, certificates, grades,
     * attendance, class visits) are computed from existing data, never stored here.
     */
    public function up(): void
    {
        Schema::create('portfolio_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('section');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->date('entry_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_entries');
    }
};
