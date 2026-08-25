<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use BelongsToSchool, HasFactory;

    public const CATEGORIES = [
        'term_start' => 'Term start',
        'term_end' => 'Term end',
        'holiday' => 'Holiday',
        'exam_period' => 'Exam period',
        'deadline' => 'Deadline',
        'event' => 'Event',
    ];

    protected $fillable = [
        'school_id',
        'created_by',
        'title',
        'description',
        'category',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
