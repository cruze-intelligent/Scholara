<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id', 'user_id', 'school_class_id', 'admission_no', 'first_name',
        'last_name', 'dob', 'gender', 'curriculum_level', 'photo_path',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardian');
    }

    public function assessmentScores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function healthRecord(): HasOne
    {
        return $this->hasOne(HealthRecord::class);
    }

    public function clinicVisits(): HasMany
    {
        return $this->hasMany(ClinicVisit::class);
    }

    public function medicationAdministrations(): HasMany
    {
        return $this->hasMany(MedicationAdministration::class);
    }

    public function dailyActivityLogs(): HasMany
    {
        return $this->hasMany(DailyActivityLog::class);
    }

    public function milestoneChecklists(): HasMany
    {
        return $this->hasMany(MilestoneChecklist::class);
    }

    public function wowMoments(): HasMany
    {
        return $this->hasMany(WowMoment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function gatePasses(): HasMany
    {
        return $this->hasMany(GatePass::class);
    }

    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
