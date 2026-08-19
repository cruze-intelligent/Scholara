<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyActivityLog extends Model
{
    protected $fillable = [
        'student_id', 'date', 'meals', 'nappy_changes', 'bathroom_breaks',
        'sleep_checks', 'logged_by', 'dirty', 'synced_at',
    ];

    protected $casts = [
        'date' => 'date',
        'meals' => 'array',
        'nappy_changes' => 'array',
        'sleep_checks' => 'array',
        'synced_at' => 'datetime',
        'dirty' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
