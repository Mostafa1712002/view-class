<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('canteen_categories')) {
            Schema::create('canteen_categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('canteen_id')->index();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('canteen_products')) {
            Schema::create('canteen_products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('canteen_id')->index();
                $table->unsignedBigInteger('canteen_category_id')->index();
                $table->string('name');
                $table->decimal('price', 8, 2)->default(0);
                $table->unsignedInteger('calories')->nullable();
                $table->string('image_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_products');
        Schema::dropIfExists('canteen_categories');
    }
};
