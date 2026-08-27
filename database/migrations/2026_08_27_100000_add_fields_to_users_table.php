<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->after('id');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('invited_at')->nullable()->after('email_verified_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'is_active', 'invited_at']);
            $table->dropSoftDeletes();
        });
    }
};
