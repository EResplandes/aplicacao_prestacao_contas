<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function __construct(private readonly Request $request) {}

    public function log(
        ?Model $user,
        string $event,
        string $action,
        Model $auditable,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $user?->getKey(),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'request_id' => $this->request->attributes->get('request_id'),
            'event' => $event,
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => array_merge($metadata, [
                'route_name' => $this->request->route()?->getName(),
                'request_method' => $this->request->getMethod(),
            ]),
            'performed_at' => now(),
        ]);
    }
}
