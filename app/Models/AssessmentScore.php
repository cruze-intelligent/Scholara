<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentScore extends Model
{
    protected $fillable = [
        'assessment_id', 'student_id', 'raw_score', 'scaled_score',
        'recorded_by', 'recorded_at', 'dirty', 'synced_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'synced_at' => 'datetime',
        'dirty' => 'boolean',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
