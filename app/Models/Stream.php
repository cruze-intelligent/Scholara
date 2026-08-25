<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A lightweight identification label for students/teachers (e.g. "Blue", "Green") — deliberately
 * not tied to a curriculum level or a single SchoolClass, since a stream is an identifying tag,
 * not a second class hierarchy (schools already model actual class sections as SchoolClass rows).
 */
class Stream extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'name'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }
}
