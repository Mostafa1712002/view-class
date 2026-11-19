<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_platforms', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 32)->unique();
            $t->string('name_ar');
            $t->string('name_en')->nullable();
            $t->string('base_url');
            $t->text('secret')->nullable(); // 'encrypted' cast
            $t->json('products')->nullable();
            $t->json('capabilities')->nullable();
            $t->json('required_fields')->nullable();
            $t->boolean('is_enabled')->default(false);
            $t->timestamp('last_ok_at')->nullable();
            $t->text('last_error')->nullable();
            $t->timestamps();
        });

        Schema::create('hub_packages', function (Blueprint $t) {
            $t->id();
            $t->string('name_ar');
            $t->string('name_en')->nullable();
            $t->text('description_ar')->nullable();
            $t->decimal('price', 10, 2)->default(0);
            $t->char('currency', 3)->default('SAR');
            $t->unsignedInteger('duration_days');
            $t->boolean('is_purchasable')->default(false);
            $t->boolean('is_active')->default(true);
            $t->integer('sort')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('hub_package_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('package_id')->constrained('hub_packages')->cascadeOnDelete();
            $t->foreignId('platform_id')->constrained('hub_platforms');
            $t->string('product_code', 32);
            $t->unique(['package_id', 'platform_id', 'product_code'], 'hub_package_items_unique');
        });

        Schema::create('hub_profiles', function (Blueprint $t) {
            $t->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $t->enum('stage', ['primary', 'intermediate', 'secondary'])->nullable();
            $t->unsignedTinyInteger('grade')->nullable();
            $t->timestamps();
        });

        Schema::create('hub_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users'); // beneficiary
            $t->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('package_id')->constrained('hub_packages');
            $t->json('items'); // snapshot [{platform_id, product_code}]
            $t->decimal('price', 10, 2)->default(0);
            $t->enum('source', ['purchase', 'grant']);
            $t->enum('status', ['pending', 'active', 'expired', 'cancelled', 'refunded'])->default('pending');
            $t->dateTime('starts_at')->nullable();
            $t->dateTime('ends_at')->nullable();
            $t->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('note', 500)->nullable();
            $t->timestamps();
            $t->index(['user_id', 'status'], 'hub_subs_user_status_idx');
            $t->index(['status', 'ends_at'], 'hub_subs_status_ends_idx');
        });

        Schema::create('hub_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('subscription_id')->unique()->constrained('hub_subscriptions');
            $t->foreignId('buyer_id')->constrained('users');
            $t->unsignedInteger('amount'); // halalas
            $t->char('currency', 3)->default('SAR');
            $t->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $t->string('moyasar_invoice_id', 64)->nullable()->unique();
            $t->string('moyasar_payment_id', 64)->nullable()->unique();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('refunded_at')->nullable();
            $t->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('refund_note', 500)->nullable();
            $t->timestamp('last_checked_at')->nullable();
            $t->timestamps();
        });

        Schema::create('hub_payment_events', function (Blueprint $t) {
            $t->id();
            $t->string('event_type', 64);
            $t->string('moyasar_payment_id', 64)->nullable()->index();
            $t->string('site', 32)->nullable();
            $t->json('payload')->nullable(); // secret_token stripped before save
            $t->string('outcome', 32); // applied|duplicate|ignored_site|unmatched|rejected
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('hub_platform_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('platform_id')->constrained('hub_platforms');
            $t->enum('role', ['student', 'parent', 'teacher']);
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('external_id', 64)->nullable();
            $t->string('login', 191)->nullable();
            $t->text('password')->nullable(); // 'encrypted' cast
            $t->boolean('managed')->default(false);
            $t->foreignId('link_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->enum('status', ['pending', 'needs_info', 'active', 'conflict', 'failed', 'disabled', 'missing', 'unlinked'])->default('pending');
            $t->boolean('enabled')->default(true);
            $t->boolean('needs_sync')->default(false);
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->timestamp('synced_at')->nullable();
            // mirror columns
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone', 64)->nullable();
            $t->string('national_id', 64)->nullable();
            $t->string('parent_external_id', 64)->nullable();
            $t->string('org_label')->nullable();
            $t->json('access')->nullable();
            $t->timestamp('seen_at')->nullable();
            $t->timestamps();
            $t->unique(['platform_id', 'role', 'external_id'], 'hub_acc_platform_role_ext_unique');
            $t->unique(['platform_id', 'role', 'user_id'], 'hub_acc_platform_role_user_unique');
            $t->index('status', 'hub_acc_status_idx');
            $t->index('needs_sync', 'hub_acc_needs_sync_idx');
            $t->index('national_id', 'hub_acc_national_id_idx');
            $t->index('email', 'hub_acc_email_idx');
            $t->index('phone', 'hub_acc_phone_idx');
        });
    }

    public function down(): void
    {
        foreach (['hub_platform_accounts', 'hub_payment_events', 'hub_payments', 'hub_subscriptions',
            'hub_profiles', 'hub_package_items', 'hub_packages', 'hub_platforms'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
