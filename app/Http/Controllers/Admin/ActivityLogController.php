<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Organization;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with(['organization', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('type') && in_array($request->type, ActivityLog::TYPES)) {
            $query->byType($request->type);
        }

        if ($request->filled('level') && in_array($request->level, ActivityLog::LEVELS)) {
            $query->byLevel($request->level);
        }

        if ($request->filled('org_id')) {
            $query->byOrg((int) $request->org_id);
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->forPeriod($request->from, $request->to);
        } elseif ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        } elseif ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $logs = $query->paginate(50)->appends($request->query());

        $organizations = Organization::orderBy('name')->get();

        return view('admin.logs.index', compact('logs', 'organizations'));
    }
}
