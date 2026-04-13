<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AuditLogExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('audit_logs.view');

        $query = AuditLog::with('user')
            ->when($request->search, function ($q) use ($request) {
                $q->where('action', 'like', "%{$request->search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->search}%"));
            })
            ->when($request->action, fn ($q) => $q->where('action', $request->action))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->from, fn ($q) => $q->where('created_at', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->where('created_at', '<=', $request->to))
            ->orderByDesc('created_at');

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $query->paginate(50)->withQueryString(),
            'filters' => $request->only(['search', 'action', 'user_id', 'from', 'to']),
        ]);
    }

    public function show(AuditLog $log): Response
    {
        $this->authorize('audit_logs.view');

        return Inertia::render('Admin/AuditLogs/Show', [
            'log' => $log->load('user'),
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('audit_logs.export');

        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after:from',
        ]);

        return Excel::download(
            new AuditLogExport($request->from, $request->to),
            "audit_log_{$request->from}_{$request->to}.xlsx"
        );
    }
}
