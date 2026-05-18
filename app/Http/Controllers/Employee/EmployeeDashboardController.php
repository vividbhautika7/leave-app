<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $totalLeaves   = LeaveApplication::where('user_id', $user->id)->count();
        $upcomingLeaves = LeaveApplication::where('user_id', $user->id)
                            ->where('start_date', '>=', today())
                            ->orderBy('start_date')
                            ->get();
        $recentLeaves  = LeaveApplication::where('user_id', $user->id)
                            ->orderBy('created_at', 'desc')
                            ->take(5)
                            ->get();

        return view('employee.dashboard', compact(
            'totalLeaves',
            'upcomingLeaves',
            'recentLeaves'
        ));
    }
}