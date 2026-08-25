<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every role's own audit trail — what THEY did (create/update/delete on an audited model; see
 * App\Models\Concerns\Auditable), not the whole school's. The platform-wide equivalent, across
 * every user and every school, is SuperAdminController::activity().
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(25);

        return view('activity-log.index', compact('logs'));
    }
}
