<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilestoneChecklist extends Model
{
    protected $fillable = ['student_id', 'domain', 'milestone_label', 'achieved_at', 'notes'];

    protected $casts = [
        'achieved_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
