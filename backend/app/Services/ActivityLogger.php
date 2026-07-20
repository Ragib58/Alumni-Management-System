<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;

/**
 * Append-only activity logger. Never throws into the caller — logging must not
 * break the primary action.
 */
class ActivityLogger
{
    public function log(
        ActivityAction|string $action,
        ?string $description = null,
        ?User $user = null,
        ?Model $subject = null,
        array $properties = [],
    ): ?ActivityLog {
        try {
            $actionValue = $action instanceof ActivityAction ? $action->value : $action;

            return ActivityLog::create([
                'user_id'      => $user?->id ?? auth()->id(),
                'action'       => $actionValue,
                'description'  => $description,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id'   => $subject?->getKey(),
                'properties'   => $properties ?: null,
                'ip_address'   => Request::ip(),
                'user_agent'   => substr((string) Request::userAgent(), 0, 1000),
                'created_at'   => Carbon::now(),
            ]);
        } catch (\Throwable) {
            return null;
        }
    }
}
