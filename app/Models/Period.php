<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Period extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_subject_assignment_id', 'day_of_week', 'start_time', 'end_time', 'room',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TeacherSubjectAssignment::class, 'teacher_subject_assignment_id');
    }

    public function getTeacherAttribute(): ?User
    {
        return $this->assignment->teacher;
    }

    public function getSubjectAttribute(): ?Subject
    {
        return $this->assignment->subject;
    }

    public function getSchoolClassAttribute(): ?SchoolClass
    {
        return $this->assignment->schoolClass;
    }
}
