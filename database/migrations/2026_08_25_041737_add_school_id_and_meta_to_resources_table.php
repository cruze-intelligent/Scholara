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
        Schema::table('resources', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('original_filename')->nullable()->after('file_path');
            $table->string('mime_type')->nullable()->after('original_filename');
            $table->unsignedBigInteger('size')->nullable()->after('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['original_filename', 'mime_type', 'size']);
        });
    }
};
