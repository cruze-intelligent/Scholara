<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Write-side audit trail for models docs/COMPLIANCE.md flags as needing one
 * (health and financial records). Read-side auditing (logging every `show`)
 * is a follow-up — see docs/ROADMAP.md.
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn (Model $model) => self::recordAudit($model, 'create'));
        static::updated(fn (Model $model) => self::recordAudit($model, 'update', $model->getChanges()));
        static::deleted(fn (Model $model) => self::recordAudit($model, 'delete'));
    }

    protected static function recordAudit(Model $model, string $action, array $changes = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'changes' => $changes ?: null,
        ]);
    }
}
