<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminLeaveController extends Controller
{
    protected GoogleCalendarService $googleService;

    public function __construct(GoogleCalendarService $googleService)
    {
        $this->googleService = $googleService;
    }

    // All leaves list
    public function index(Request $request)
    {
        $query = LeaveApplication::with('user')->orderBy('start_date', 'desc');

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('user_id', $request->employee_id);
        }

        // Filter by month
        if ($request->filled('month')) {
            $query->whereMonth('start_date', Carbon::parse($request->month)->month)
                ->whereYear('start_date', Carbon::parse($request->month)->year);
        }

        $leaves    = $query->get();
        $employees = User::where('role', 'employee')->get();

        return view('admin.leaves.index', compact('leaves', 'employees'));
    }

    // Show single leave detail
    public function show(LeaveApplication $leave)
    {
        $leave->load('user');
        return view('admin.leaves.show', compact('leave'));
    }

    // Delete leave by admin
    public function destroy(LeaveApplication $leave)
    {
        // Step 1 — Delete from Google Calendar first
        if ($leave->google_event_id) {
            $this->googleService->deleteLeaveEvent($leave);
        }

        // Step 2 — Permanently delete from database
        $leave->forceDelete();

        return back()->with('success', 'Leave deleted from system and Google Calendar.');
    }
}
