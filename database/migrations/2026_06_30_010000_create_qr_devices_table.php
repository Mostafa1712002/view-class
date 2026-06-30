<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #265 — IoT scanner devices registry. A device authenticates scans via its
 * unique device_key (the qr_scans.channel='iot' path). Tenant-owned, soft-delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('name');
            $table->string('device_key', 64)->unique();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_devices');
    }
};
