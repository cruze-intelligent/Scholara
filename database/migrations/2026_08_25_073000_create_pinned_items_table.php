<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A user's own dashboard shortcuts — presence of a row means "shown." Used two ways from the
 * same table: an opt-in pin of a tab (key = its route name, e.g. 'reports.academics') and the
 * one opt-out case, the calendar preview widget, which is visible by default and hidden only
 * once a row with key 'calendar_dismissed' exists. See App\Support\PinnableTabs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinned_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinned_items');
    }
};
