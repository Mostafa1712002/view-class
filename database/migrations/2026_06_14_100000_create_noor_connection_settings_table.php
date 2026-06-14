<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Noor Integration — Method 2 (Official API) & Method 3 (Admin Credentials).
 *
 * SCAFFOLDED ONLY — no live calls are made to Noor in the current release.
 * All sensitive credential fields are stored encrypted (Laravel Crypt).
 * The active_method field controls which import path the system uses:
 *   excel       → current primary path (manual Excel upload)
 *   api         → Method 2: official Noor API (requires Ministry approval)
 *   credential  → Method 3: admin credential-based access (requires approval)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('noor_connection_settings', function (Blueprint $table) {
            $table->id();
            $table->string('active_method', 16)->default('excel')
                ->comment('excel|api|credential');
            $table->string('api_base_url', 255)->nullable()
                ->comment('Base URL for the official Noor API (Method 2)');
            // Encrypted: never stored as plaintext.
            $table->text('api_token_encrypted')->nullable()
                ->comment('API bearer token — Crypt::encryptString()');
            $table->string('admin_username', 255)->nullable()
                ->comment('Noor admin username for credential-based access (Method 3)');
            $table->text('admin_password_encrypted')->nullable()
                ->comment('Noor admin password — Crypt::encryptString(), NEVER plaintext');
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status', 32)->nullable()
                ->comment('Result of last sync: success|failed|not_activated');
            $table->timestamps();
        });

        Schema::create('noor_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method', 16)->default('excel')
                ->comment('excel|api|credential');
            $table->string('status', 32)->default('pending')
                ->comment('pending|running|completed|failed|not_activated');
            $table->foreignId('school_id')->nullable()->index();
            $table->foreignId('triggered_by')->nullable()
                ->comment('user_id who triggered this sync');
            $table->text('note')->nullable();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noor_sync_logs');
        Schema::dropIfExists('noor_connection_settings');
    }
};
