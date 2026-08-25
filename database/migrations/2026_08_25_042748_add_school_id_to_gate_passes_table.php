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
        Schema::table('gate_passes', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gate_passes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn('status');
        });
    }
};
