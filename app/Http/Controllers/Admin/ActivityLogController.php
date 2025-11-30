<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ActivityLog::with(['user'])
            ->latest();

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', "%{$request->search}%");
        }

        $logs = $query->paginate(50);

        $actions = ActivityLog::select('action')
            ->distinct()
            ->pluck('action');

        return view('admin.activity-logs.index', compact('logs', 'actions'));
    }

    public function show(ActivityLog $activityLog)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && $activityLog->school_id !== $user->school_id) {
            abort(403);
        }

        return view('admin.activity-logs.show', compact('activityLog'));
    }

    public function destroy(ActivityLog $activityLog)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin()) {
            abort(403);
        }

        $activityLog->delete();

        return redirect()->route('admin.activity-logs.index')
            ->with('success', 'تم حذف السجل بنجاح');
    }

    public function clear(Request $request)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin()) {
            abort(403);
        }

        $daysToKeep = $request->input('days', 30);

        ActivityLog::where('created_at', '<', now()->subDays($daysToKeep))->delete();

        return redirect()->route('admin.activity-logs.index')
            ->with('success', "تم حذف السجلات الأقدم من {$daysToKeep} يوم");
    }
}
