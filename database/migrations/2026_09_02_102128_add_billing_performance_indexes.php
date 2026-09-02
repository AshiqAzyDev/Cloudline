<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('issue_date');
            $table->index('paid_at');
            $table->index('stripe_payment_intent_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('received_at');
            $table->unique('stripe_payment_intent_id');
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->index(['scheduled_for', 'sent_at', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['issue_date']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['stripe_payment_intent_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['received_at']);
            $table->dropUnique(['stripe_payment_intent_id']);
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex(['scheduled_for', 'sent_at', 'cancelled_at']);
        });
    }
};
