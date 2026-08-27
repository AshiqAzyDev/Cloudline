<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name');
            $table->string('slug')->unique();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('invoice_prefix', 10);
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->unsignedSmallInteger('numbering_year')->nullable();
            $table->string('default_currency', 3)->default('GBP');
            $table->decimal('default_vat_rate', 5, 2)->default(20);
            $table->unsignedSmallInteger('default_due_days')->default(14);
            $table->text('bank_details')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->text('terms')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('contact')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('vat_number')->nullable();
            $table->string('default_currency', 3)->default('GBP');
            $table->string('vat_treatment')->default('standard');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_entity_id')->nullable()->constrained('billing_entities')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('default_rate_minor')->default(0);
            $table->string('currency', 3)->default('GBP');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->foreignId('billing_entity_id')->constrained('billing_entities')->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('currency', 3)->default('GBP');
            $table->string('status')->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->unsignedBigInteger('subtotal_minor')->default(0);
            $table->boolean('vat_enabled')->default(true);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->unsignedBigInteger('vat_minor')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->unsignedBigInteger('amount_paid_minor')->default(0);
            $table->string('vat_treatment')->default('standard');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->ulid('pay_token')->unique();
            $table->string('stripe_checkout_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->decimal('fx_rate_to_gbp', 12, 6)->nullable();
            $table->unsignedBigInteger('total_gbp_minor')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_date']);
            $table->index(['client_id', 'status']);
            $table->index(['billing_entity_id', 'issue_date']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('qty', 10, 2)->default(1);
            $table->unsignedBigInteger('unit_price_minor')->default(0);
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_balance_transaction_id')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->unsignedBigInteger('fee_minor')->default(0);
            $table->unsignedBigInteger('net_minor')->default(0);
            $table->string('settlement_currency', 3)->nullable();
            $table->unsignedBigInteger('settlement_amount_minor')->nullable();
            $table->string('method')->default('stripe');
            $table->timestamp('received_at');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('reminder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_entity_id')->constrained('billing_entities')->cascadeOnDelete();
            $table->smallInteger('offset_days');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reminder_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->smallInteger('offset_days');
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('channel')->default('email');
            $table->boolean('is_manual')->default(false);
            $table->timestamps();
        });

        Schema::create('stripe_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type');
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        Schema::dropIfExists('settings');
        Schema::dropIfExists('stripe_events');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('reminder_rules');
        Schema::dropIfExists('invoice_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('services');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('billing_entities');
    }
};
