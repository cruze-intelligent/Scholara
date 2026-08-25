<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTag extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'student_id', 'tag', 'note', 'tagged_by'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function taggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tagged_by');
    }
}
