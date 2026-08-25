<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Generic "attach a file to a record" model — a student's medical dosage sheet, a staff
 * member's contract/certificate, etc. Deliberately generic (polymorphic `documentable`) rather
 * than a bespoke upload path per feature, since the shape (file + who uploaded it + what it's
 * attached to) is identical across every case that needed one.
 */
class Document extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'documentable_type', 'documentable_id', 'uploaded_by', 'category',
        'title', 'file_path', 'original_filename', 'mime_type', 'size',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
