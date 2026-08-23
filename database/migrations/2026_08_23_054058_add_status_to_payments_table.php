<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending')->after('reference');
            $table->string('provider')->nullable()->after('status');
            $table->string('currency', 3)->default('UGX')->after('amount');
            $table->timestamp('paid_at')->nullable()->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['mobile_money', 'card', 'schoolpay', 'bank', 'cash'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['status', 'provider', 'currency']);
            $table->timestamp('paid_at')->nullable(false)->change();
            $table->enum('method', ['mobile_money', 'schoolpay', 'bank', 'cash'])->change();
        });
    }
};
