<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('stream_id')->nullable()->after('school_class_id')->constrained()->nullOnDelete();
        });

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->foreignId('stream_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stream_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stream_id');
        });

        Schema::dropIfExists('streams');
    }
};
