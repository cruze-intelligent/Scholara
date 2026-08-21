<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationAdministration extends Model
{
    use Auditable;

    public const RIGHTS = [
        'checked_right_patient' => 'Right patient',
        'checked_right_drug' => 'Right drug',
        'checked_right_dose' => 'Right dose',
        'checked_right_route' => 'Right route',
        'checked_right_time' => 'Right time',
    ];

    protected $fillable = [
        'student_id', 'medication_name', 'dose', 'route', 'administered_by',
        'administered_at', 'scheduled_time', 'checked_right_patient', 'checked_right_drug',
        'checked_right_dose', 'checked_right_route', 'checked_right_time', 'notes',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
        'scheduled_time' => 'datetime',
        'checked_right_patient' => 'boolean',
        'checked_right_drug' => 'boolean',
        'checked_right_dose' => 'boolean',
        'checked_right_route' => 'boolean',
        'checked_right_time' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function getFiveRightsCheckedAttribute(): bool
    {
        return collect(array_keys(self::RIGHTS))->every(fn ($field) => (bool) $this->{$field});
    }
}
