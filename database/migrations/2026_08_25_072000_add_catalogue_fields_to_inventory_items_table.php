<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Library-specific catalogue fields — nullable and only ever populated for category='library'
 * rows. Kept on the existing inventory_items table rather than a separate books table so the
 * BookLoanController/InventoryTransactionObserver circulation machinery built around InventoryItem
 * doesn't need a second, parallel model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('author')->nullable()->after('name');
            $table->string('isbn')->nullable()->after('author');
            $table->string('publisher')->nullable()->after('isbn');
            $table->unsignedSmallInteger('edition_year')->nullable()->after('publisher');
            $table->string('shelf_location')->nullable()->after('edition_year');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['author', 'isbn', 'publisher', 'edition_year', 'shelf_location']);
        });
    }
};
