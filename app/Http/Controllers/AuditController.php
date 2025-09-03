<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    /**
     * Get audit trail data
     */
    public function index(Request $request)
    {
        Gate::authorize('export_audit_trail');

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'user_id' => 'nullable|exists:users,id',
            'event' => 'nullable|string',
            'auditable_type' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Audit::with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->event) {
            $query->where('event', $request->event);
        }
        if ($request->auditable_type) {
            $query->where('auditable_type', $request->auditable_type);
        }

        $perPage = $request->per_page ?? 25;
        $auditLogs = $query->paginate($perPage);

        return response()->json([
            'data' => $auditLogs->items(),
            'pagination' => [
                'current_page' => $auditLogs->currentPage(),
                'last_page' => $auditLogs->lastPage(),
                'per_page' => $auditLogs->perPage(),
                'total' => $auditLogs->total(),
            ],
        ]);
    }
}