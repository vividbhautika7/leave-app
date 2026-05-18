<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    protected GoogleCalendarService $googleService;

    public function __construct(GoogleCalendarService $googleService)
    {
        $this->googleService = $googleService;
    }

    // Redirect to Google OAuth
    public function redirect()
    {
        // Make sure only admin can connect
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Admins only.');
        }

        $authUrl = $this->googleService->getAuthUrl();
        return redirect($authUrl);
    }

    // Handle callback from Google
    public function callback(Request $request)
    {
        // Make sure only admin can connect
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Admins only.');
        }

        if (!$request->has('code')) {
            return redirect()->route('admin.dashboard')
                             ->with('error', 'Google authorization failed. Please try again.');
        }

        try {
            $this->googleService->handleCallback($request->get('code'));

            // After successful connection, re-sync all existing leaves to Google Calendar
            $this->resyncAllLeaves();

            return redirect()->route('admin.dashboard')
                             ->with('success', '✅ Google Calendar connected successfully!');

        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')
                             ->with('error', 'Google Calendar connection failed: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Admins only.');
        }

        // Clear tokens from database
        Auth::user()->update([
            'google_token'         => null,
            'google_refresh_token' => null,
        ]);

        // Delete all events from Google Calendar
        $this->deleteAllCalendarEvents();

        return redirect()->route('admin.dashboard')
                         ->with('success', '✅ Google Calendar disconnected successfully!');
    }



    // Delete all leave events from Google Calendar
    private function deleteAllCalendarEvents(): void
    {
        // Get all leaves that have a Google Calendar event
        $leaves = DB::table('leave_applications')
                    ->whereNotNull('google_event_id')
                    ->select('id', 'google_event_id')
                    ->get();

        if ($leaves->isEmpty()) {
            return;
        }

        foreach ($leaves as $row) {
            try {
                // Build minimal model object with just google_event_id
                $leave                  = new LeaveApplication();
                $leave->google_event_id = $row->google_event_id;

                $this->googleService->deleteLeaveEvent($leave);

                // Clear google_event_id from DB
                DB::table('leave_applications')
                    ->where('id', $row->id)
                    ->update(['google_event_id' => null]);

                Log::info("Disconnect: Calendar event deleted for leave ID: {$row->id}");

            } catch (\Exception $e) {
                Log::error("Disconnect: Failed to delete event for leave ID {$row->id}: " . $e->getMessage());
            }
        }
    }

    // Re-sync all leaves to Google Calendar after reconnect
    private function resyncAllLeaves(): void
    {
        $leaves = LeaveApplication::with('user')->get();

        if ($leaves->isEmpty()) {
            return;
        }

        foreach ($leaves as $leave) {
            try {
                $eventId = $this->googleService->createLeaveEvent($leave);

                if ($eventId) {
                    $leave->update(['google_event_id' => $eventId]);
                    Log::info("Reconnect: Event created for {$leave->user->name}");
                }

            } catch (\Exception $e) {
                Log::error("Reconnect: Failed for leave ID {$leave->id}: " . $e->getMessage());
            }
        }
    }
}