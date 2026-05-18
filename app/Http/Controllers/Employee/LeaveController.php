<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\LeaveReminder;
use App\Services\GoogleCalendarService;
use App\Notifications\LeaveAppliedNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    protected GoogleCalendarService $googleService;

    public function __construct(GoogleCalendarService $googleService)
    {
        $this->googleService = $googleService;
    }

    // Show apply leave form
    public function create()
    {
        return view('employee.leave.apply');
    }


    // app/Http/Controllers/Employee/LeaveController.php

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['required', 'string', 'min:10', 'max:500'],
        ]);

        // Check if applying for today's leave after 12PM
        // $startDate = \Carbon\Carbon::parse($request->start_date);
        // if ($startDate->isToday()) {
        //     $currentTime = \Carbon\Carbon::now(config('app.timezone', 'Asia/Kolkata'));

        //     if ($currentTime->hour >= 12) {
        //         return back()
        //             ->with('error', 'Same day leave can only be applied before 12:00 PM. Please apply for tomorrow or a future date.')
        //             ->withInput();
        //     }
        // }

        // Check duplicate leave
        $existing = LeaveApplication::where('user_id', Auth::id())
            ->where(function ($query) use ($request) {
                $query->where('start_date', '<=', $request->end_date)
                    ->where('end_date', '>=', $request->start_date);
            })->exists();

        if ($existing) {
            return back()
                ->with('error', 'You already have a leave applied on these dates.')
                ->withInput();
        }

        $startDate   = \Carbon\Carbon::parse($request->start_date);
        $endDate     = \Carbon\Carbon::parse($request->end_date);


        // Create leave
        $leave = LeaveApplication::create([
            'user_id'      => Auth::id(),
            'start_date'   => $request->start_date,
            'end_date'     => $request->end_date,
            'reason'       => $request->reason,
        ]);

        $leave->load('user');

        // Add to Google Calendar
        $eventId = $this->googleService->createLeaveEvent($leave);
        if ($eventId) {
            $leave->update(['google_event_id' => $eventId]);
        }

        // Create reminders
        $this->createReminders($leave);

        // Notify admin
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new LeaveAppliedNotification($leave));
        }

        return redirect()->route('employee.leave.my')
            ->with('success', "Leave application submitted!");
    }

    // Show all leaves of logged in employee
    public function myLeaves()
    {
        $leaves = LeaveApplication::where('user_id', Auth::id())
            ->orderBy('start_date', 'desc')
            ->get();

        return view('employee.leave.my-leaves', compact('leaves'));
    }

    // Delete a leave application
    public function destroy(LeaveApplication $leave)
    {
        // Make sure employee can only delete their own leave
        if ($leave->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow delete if leave hasn't started yet
        if ($leave->start_date->isPast()) {
            return back()->with('error', 'Cannot delete a leave that has already started.');
        }

        // Delete from Google Calendar
        $this->googleService->deleteLeaveEvent($leave);

        // Delete reminders for this leave's dates
        $this->deleteReminders($leave);

        $leave->delete();

        return back()->with('success', 'Leave application deleted successfully.');
    }

    private function createReminders(LeaveApplication $leave): void
    {
        $current = $leave->start_date->copy();

        while ($current->lte($leave->end_date)) {
            $leaveDate = $current->format('Y-m-d');
            $dayBefore = $current->copy()->subDay()->format('Y-m-d');

            // Day before reminder
            LeaveReminder::firstOrCreate([
                'reminder_date' => $dayBefore,
                'leave_date'    => $leaveDate,
            ], [
                'sent' => false,
            ]);

            // Day of reminder
            LeaveReminder::firstOrCreate([
                'reminder_date' => $leaveDate,
                'leave_date'    => $leaveDate,
            ], [
                // If leave starts today, mark as sent already
                // because we already sent immediate notification above
                'sent' => $current->isToday() ? true : false,
            ]);

            $current->addDay();
        }
    }

    // Delete reminders when leave is cancelled
    private function deleteReminders(LeaveApplication $leave): void
    {
        $current = $leave->start_date->copy();

        while ($current->lte($leave->end_date)) {
            $leaveDate = $current->format('Y-m-d');

            LeaveReminder::where('leave_date', $leaveDate)->delete();

            $current->addDay();
        }
    }
}
