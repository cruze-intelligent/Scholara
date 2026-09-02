<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->enum('curriculum_level', ['nursery', 'primary', 'lower_secondary', 'upper_secondary']);
            $table->string('term');
            $table->string('label')->default('Tuition');
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->timestamps();

            $table->unique(['school_id', 'curriculum_level', 'term', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
