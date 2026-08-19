<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('nin')->nullable()->after('email');
            $table->string('phone')->nullable()->after('nin');
            $table->string('two_factor_otp')->nullable()->after('phone');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_otp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['nin', 'phone', 'two_factor_otp', 'two_factor_expires_at']);
        });
    }
};
