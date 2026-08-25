<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PinnedItem extends Model
{
    protected $fillable = ['user_id', 'key'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
