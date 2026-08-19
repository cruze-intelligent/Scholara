<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationAdministration extends Model
{
    protected $fillable = [
        'student_id', 'medication_name', 'dose', 'administered_by',
        'administered_at', 'five_rights_checked', 'notes',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
        'five_rights_checked' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }
}
