<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The standard fee for a curriculum level in a given term (e.g. "Primary, Term 2, Tuition —
 * 450,000 UGX") — what a bursar generates real per-student Invoice rows from, rather than typing
 * the same amount in by hand for every student in that level.
 */
class FeeStructure extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'curriculum_level', 'term', 'label', 'amount', 'due_date'];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];
}
