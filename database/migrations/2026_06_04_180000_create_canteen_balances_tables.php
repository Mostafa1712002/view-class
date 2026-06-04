<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('canteen_balances')) {
            Schema::create('canteen_balances', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->decimal('balance', 10, 2)->default(0);
                $table->decimal('daily_limit', 10, 2)->nullable(); // set by the parent (part 4)
                $table->timestamps();

                $table->unique(['school_id', 'student_id']);
            });
        }

        // Every balance change is logged here — money must never move without a trace.
        if (! Schema::hasTable('canteen_balance_transactions')) {
            Schema::create('canteen_balance_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->enum('type', ['add', 'deduct', 'set']);
                $table->decimal('amount', 10, 2);          // the operation amount (always positive)
                $table->decimal('balance_after', 10, 2);   // resulting balance, for audit
                $table->string('note', 500)->nullable();
                $table->string('source', 50)->default('admin'); // admin | order | refund (later slices)
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_balance_transactions');
        Schema::dropIfExists('canteen_balances');
    }
};
