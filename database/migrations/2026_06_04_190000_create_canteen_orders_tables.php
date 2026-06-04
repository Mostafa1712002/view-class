<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('canteen_orders')) {
            Schema::create('canteen_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('canteen_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->enum('status', ['new', 'confirmed', 'prepared', 'delivered', 'cancelled'])->default('new')->index();
                $table->decimal('total', 10, 2)->default(0);
                $table->boolean('charged')->default(false); // was the balance deducted?
                $table->string('note', 500)->nullable();
                $table->unsignedBigInteger('placed_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('canteen_order_items')) {
            Schema::create('canteen_order_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('canteen_order_id')->index();
                $table->unsignedBigInteger('canteen_product_id')->nullable();
                $table->string('product_name');           // snapshot at order time
                $table->decimal('unit_price', 10, 2);      // snapshot — price changes later don't alter past orders
                $table->unsignedInteger('quantity');
                $table->decimal('line_total', 10, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('canteen_blocked_products')) {
            Schema::create('canteen_blocked_products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('canteen_product_id')->index();
                $table->unsignedBigInteger('blocked_by')->nullable();
                $table->timestamps();
                $table->unique(['student_id', 'canteen_product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_blocked_products');
        Schema::dropIfExists('canteen_order_items');
        Schema::dropIfExists('canteen_orders');
    }
};
