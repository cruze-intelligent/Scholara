<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatePass extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'requested_by', 'reason', 'status', 'approved_by',
        'departed_at', 'returned_at',
    ];

    protected $casts = [
        'departed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
