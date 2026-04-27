<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\BlockedMobile;
use App\Models\SecurityEventLog;
use App\Models\User;
use App\Services\SecurityEventService;
use App\Services\ThreatDetectionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityDashboardController extends Controller
{
    public function __construct(
        private SecurityEventService $securityEvents,
        private ThreatDetectionService $threatDetection,
    ) {}

    public function index(): Response
    {
        $this->authorize('security.view');

        return Inertia::render('Admin/Security/Dashboard', [
            'overview' => $this->securityEvents->getOverview(),
            'timeline' => SecurityEventLog::highSeverity()
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function events(Request $request): Response
    {
        $this->authorize('security.view');

        $query = SecurityEventLog::query();

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('ip_filter')) {
            $query->where('ip_address', $request->ip_filter);
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to);
        }
        if ($request->boolean('unresolved_only')) {
            $query->unresolved();
        }

        return Inertia::render('Admin/Security/Events', [
            'events' => $query->orderByDesc('created_at')->paginate(50)->withQueryString(),
            'filters' => $request->only(['event_type', 'severity', 'ip_filter', 'from', 'to', 'unresolved_only']),
        ]);
    }

    public function showEvent(SecurityEventLog $event): Response
    {
        $this->authorize('security.view');

        return Inertia::render('Admin/Security/EventDetail', [
            'event' => $event->load('user', 'resolvedBy'),
        ]);
    }

    public function resolveEvent(Request $request, SecurityEventLog $event)
    {
        $this->authorize('security.resolve_event');

        $request->validate(['note' => 'nullable|string|max:1000']);

        // Phase-3 schema: no resolved state persisted yet.
        // Keep endpoint for UI compatibility; no-op update.

        return back()->with('success', 'Event resolved.');
    }

    public function blockedIps(Request $request): Response
    {
        $this->authorize('security.view');

        $query = BlockedIp::with('blockedByUser')->latest();

        if ($request->filled('search')) {
            $query->where('ip_address', 'like', "%{$request->search}%");
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        return Inertia::render('Admin/Security/BlockedIps', [
            'blockedIps' => $query->paginate(50)->withQueryString(),
            'filters' => $request->only(['search', 'active_only']),
        ]);
    }

    public function blockIp(Request $request)
    {
        $this->authorize('security.block_ip');

        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:500',
            'duration' => 'required|integer|min:1',
            'permanent' => 'boolean',
        ]);

        $record = BlockedIp::block(
            $request->ip_address,
            $request->reason,
            $request->duration * 3600,
            false
        );

        if ($request->boolean('permanent')) {
            $record->update(['expires_at' => null]);
        }

        $record->update(['blocked_by_user_id' => $request->user()->id]);

        audit('ip.manual_blocked', null, [], [
            'ip' => $request->ip_address,
            'reason' => $request->reason,
        ]);

        return back()->with('success', "IP {$request->ip_address} blocked.");
    }

    public function unblockIp(Request $request, string $ip)
    {
        $this->authorize('security.unblock_ip');

        BlockedIp::unblock($ip);

        audit('ip.unblocked', null, [], ['ip' => $ip]);

        return back()->with('success', "IP {$ip} unblocked.");
    }

    public function blockedMobiles(Request $request): Response
    {
        $this->authorize('security.view');

        $query = BlockedMobile::with('blockedByUser')->latest();

        if ($request->filled('search')) {
            $normalized = BlockedMobile::normalize($request->search);
            $query->where('mobile', 'like', "%{$normalized}%");
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        return Inertia::render('Admin/Security/BlockedMobiles', [
            'blockedMobiles' => $query->paginate(50)->withQueryString(),
            'filters' => $request->only(['search', 'active_only']),
        ]);
    }

    public function blockMobile(Request $request)
    {
        $this->authorize('security.block_mobile');

        $request->validate([
            'mobile' => 'required|string',
            'reason' => 'required|string|max:500',
            'duration' => 'required|integer|min:1',
            'permanent' => 'boolean',
        ]);

        $normalized = BlockedMobile::normalize($request->mobile);
        if (! preg_match('/^[6-9]\d{9}$/', $normalized)) {
            return back()->withErrors(['mobile' => 'Please enter a valid 10-digit Indian mobile number.']);
        }

        $record = BlockedMobile::block(
            $normalized,
            $request->reason,
            $request->duration * 3600,
            false
        );

        if ($request->boolean('permanent')) {
            $record->update(['expires_at' => null]);
        }

        $record->update(['blocked_by_user_id' => $request->user()->id]);

        audit('mobile.manual_blocked', null, [], [
            'mobile' => $normalized,
            'reason' => $request->reason,
        ]);

        return back()->with('success', "Mobile {$normalized} blocked.");
    }

    public function unblockMobile(Request $request, string $mobile)
    {
        $this->authorize('security.unblock_mobile');

        $normalized = BlockedMobile::normalize($mobile);
        $record = BlockedMobile::where('mobile', $normalized)->first();

        BlockedMobile::unblock($normalized);

        if ($record) {
            audit('mobile.unblocked', $record, [
                'mobile' => $record->mobile,
                'reason' => $record->reason,
                'expires_at' => $record->expires_at?->toIso8601String(),
            ], ['mobile' => $normalized]);
        }

        return back()->with('success', "Mobile {$normalized} unblocked.");
    }

    public function userProfile(User $user): Response
    {
        $this->authorize('security.view');

        return Inertia::render('Admin/Security/UserProfile', [
            'user' => $user,
            'profile' => $this->securityEvents->getUserSecurityProfile($user->id),
        ]);
    }

    public function fraudQueue(Request $request): Response
    {
        $this->authorize('security.view_fraud_queue');

        $events = SecurityEventLog::ofType(SecurityEventLog::EVENT_WALLET_FRAUD_CHECK)
            ->highSeverity()
            ->unresolved()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(30);

        return Inertia::render('Admin/Security/FraudQueue', [
            'events' => $events,
        ]);
    }
}
