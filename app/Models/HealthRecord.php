<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecord extends Model
{
    use Auditable;

    protected $fillable = [
        'student_id', 'chronic_conditions', 'allergies', 'vaccinations',
        'emergency_contacts', 'family_physician',
    ];

    protected $casts = [
        'chronic_conditions' => 'array',
        'allergies' => 'array',
        'vaccinations' => 'array',
        'emergency_contacts' => 'array',
        'family_physician' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
