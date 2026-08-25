<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Self-registration + subscription state. `status` defaults to 'active' so every school that
 * already exists (seeded demo schools, anything created before this migration) keeps working
 * unchanged — only a school created through the new self-registration flow is deliberately
 * started at 'pending_review'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('registration_number')->nullable()->unique()->after('name');
            // Ministry of Education registration number — a documented placeholder field, like
            // the PAYE/NSSF rates elsewhere in this app; verify the real requirement before
            // relying on it to actually confirm a school's legitimacy.
            $table->string('moe_registration_number')->nullable()->after('registration_number');
            $table->string('status')->default('active')->after('logo_path');
            $table->timestamp('trial_ends_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['registration_number', 'moe_registration_number', 'status', 'trial_ends_at']);
        });
    }
};
