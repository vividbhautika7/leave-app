<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Stats
        $totalEmployees  = User::where('role', 'employee')->count();
        $totalLeaves     = LeaveApplication::count();
        $todayLeaves     = LeaveApplication::onDate(today())->with('user')->get();
        $upcomingLeaves  = LeaveApplication::where('start_date', '>', today())
                            ->orderBy('start_date')
                            ->with('user')
                            ->take(5)
                            ->get();

        // Calendar data — group leaves by date for current month
        $currentMonth  = Carbon::now()->startOfMonth();
        $endOfMonth    = Carbon::now()->endOfMonth();

        $monthLeaves = LeaveApplication::whereBetween('start_date', [$currentMonth, $endOfMonth])
                        ->orWhereBetween('end_date', [$currentMonth, $endOfMonth])
                        ->with('user')
                        ->get();

        // Google Calendar embed URL from .env
        $googleCalendarUrl = config('services.google.calendar_embed_url');

        return view('admin.dashboard', compact(
            'totalEmployees',
            'totalLeaves',
            'todayLeaves',
            'upcomingLeaves',
            'googleCalendarUrl'  // removed calendarMap, added this
        ));
    }
}