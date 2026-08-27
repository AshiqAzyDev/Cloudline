<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_entities', function (Blueprint $table) {
            $table->string('address_line1')->nullable()->after('address');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('postcode', 32)->nullable()->after('city');
            $table->string('country')->nullable()->after('postcode');
            $table->string('bank_name')->nullable()->after('bank_details');
            $table->string('account_name')->nullable()->after('bank_name');
            $table->string('sort_code', 32)->nullable()->after('account_name');
            $table->string('account_number', 64)->nullable()->after('sort_code');
            $table->string('iban', 64)->nullable()->after('account_number');
            $table->string('bic', 32)->nullable()->after('iban');
        });
    }

    public function down(): void
    {
        Schema::table('billing_entities', function (Blueprint $table) {
            $table->dropColumn([
                'address_line1',
                'address_line2',
                'city',
                'postcode',
                'country',
                'bank_name',
                'account_name',
                'sort_code',
                'account_number',
                'iban',
                'bic',
            ]);
        });
    }
};
