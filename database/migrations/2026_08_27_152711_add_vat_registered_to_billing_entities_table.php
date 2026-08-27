<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_entities', function (Blueprint $table) {
            $table->boolean('vat_registered')->default(true)->after('vat_number');
        });
    }

    public function down(): void
    {
        Schema::table('billing_entities', function (Blueprint $table) {
            $table->dropColumn('vat_registered');
        });
    }
};
