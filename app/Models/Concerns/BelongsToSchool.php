<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Auto-scopes queries to the authenticated user's school and stamps
 * school_id on create, closing the "never a raw all-students query"
 * requirement in docs/COMPLIANCE.md. No-ops outside an authenticated
 * context (console/seeders), matching existing seeder behavior.
 */
trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $builder) {
            if (auth()->check() && auth()->user()->school_id) {
                $builder->where($builder->getModel()->getTable().'.school_id', auth()->user()->school_id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->school_id) && auth()->check()) {
                $model->school_id = auth()->user()->school_id;
            }
        });
    }
}
