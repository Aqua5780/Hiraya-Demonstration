<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'action', 'entity', 'date']);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($filters['q'] ?? null, function ($query, $value): void {
                $query->where(function ($query) use ($value): void {
                    $query->where('action_type', 'like', "%{$value}%")
                        ->orWhere('entity_name', 'like', "%{$value}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$value}%")->orWhere('email', 'like', "%{$value}%"));
                });
            })
            ->when($filters['action'] ?? null, fn ($query, $value) => $query->where('action_type', $value))
            ->when($filters['entity'] ?? null, fn ($query, $value) => $query->where('entity_name', $value))
            ->when($filters['date'] ?? null, fn ($query, $value) => $query->whereDate('created_at', $value))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'user' => $log->user?->name,
                'email' => $log->user?->email,
                'action_type' => $log->action_type,
                'entity_name' => $log->entity_name,
                'record_id' => $log->record_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => AuditLog::query()->distinct()->orderBy('action_type')->pluck('action_type'),
            'entities' => AuditLog::query()->distinct()->orderBy('entity_name')->pluck('entity_name'),
        ]);
    }
}
