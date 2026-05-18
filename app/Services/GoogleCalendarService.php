<?php

namespace App\Services;

use App\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventReminder;
use Google\Service\Calendar\EventReminders;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected Client $client;
    protected string $calendarId;
    protected string $timezone;

    public function __construct()
    {
        $this->calendarId = config('services.google.calendar_id');
        $this->timezone   = config('app.timezone', 'Asia/Kolkata');
        $this->client     = $this->buildClient();
    }

    private function buildClient(): Client
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope(Calendar::CALENDAR);
        $client->addScope(Calendar::CALENDAR_EVENTS);

        $admin = User::where('role', 'admin')->first();

        if ($admin && $admin->google_token) {
            $token = [
                'access_token'  => $admin->google_token,
                'refresh_token' => $admin->google_refresh_token,
                'expires_in'    => 3600,
                'created'       => time() - 3700,
            ];

            $client->setAccessToken($token);

            if ($client->isAccessTokenExpired()) {
                if ($admin->google_refresh_token) {
                    $newToken = $client->fetchAccessTokenWithRefreshToken(
                        $admin->google_refresh_token
                    );
                    if (!isset($newToken['error'])) {
                        $admin->update([
                            'google_token'         => $newToken['access_token'],
                            'google_refresh_token' => $newToken['refresh_token']
                                ?? $admin->google_refresh_token,
                        ]);
                        $client->setAccessToken($newToken);
                    } else {
                        Log::error('Google token refresh failed: ', $newToken);
                    }
                }
            }
        } else {
            Log::warning('Google Calendar: Admin has no token saved.');
        }

        return $client;
    }

    private function getService(): Calendar
    {
        return new Calendar($this->client);
    }

    public function createLeaveEvent(LeaveApplication $leave): ?string
    {
        try {
            $service      = $this->getService();
            $employeeName = $leave->user->name;

            // Get date string from leave — works for both date and datetime cast
            $startDate = Carbon::parse($leave->start_date)->format('Y-m-d');
            $endDate   = Carbon::parse($leave->end_date)->format('Y-m-d');

            // Check if leave starts today using Carbon::today()
            $isToday = ($startDate === Carbon::today($this->timezone)->format('Y-m-d'));

            Log::info('createLeaveEvent called', [
                'employee'  => $employeeName,
                'startDate' => $startDate,
                'isToday'   => $isToday,
                'timezone'  => $this->timezone,
            ]);

            if ($isToday) {
                // ─────────────────────────────────────────────
                // URGENT / SAME DAY LEAVE
                // Create timed event starting RIGHT NOW
                // 0 min reminder = fires immediately ✅
                // ─────────────────────────────────────────────
                $eventId = $this->createUrgentLeaveEvent(
                    $service,
                    $leave,
                    $employeeName,
                    $startDate,
                    $endDate
                );
            } else {
                // ─────────────────────────────────────────────
                // FUTURE LEAVE
                // Create all-day display event +
                // Separate 9AM reminder event on leave day
                // ─────────────────────────────────────────────
                $eventId = $this->createFutureLeaveEvent(
                    $service,
                    $leave,
                    $employeeName,
                    $startDate,
                    $endDate
                );
            }

            return $eventId;

        } catch (\Google\Service\Exception $e) {
            Log::error('Google API Error in createLeaveEvent: ' . $e->getMessage(), [
                'code'   => $e->getCode(),
                'errors' => $e->getErrors(),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('createLeaveEvent failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    // URGENT: Same day leave — fires notification immediately
    private function createUrgentLeaveEvent(
        Calendar $service,
        LeaveApplication $leave,
        string $employeeName,
        string $startDate,
        string $endDate
    ): ?string {
        // Current time in correct timezone
        $nowTime = Carbon::now($this->timezone)->format('Y-m-d\TH:i:s');
        $endTime = $endDate . 'T18:00:00';

        $event = new Event();
        $event->setSummary("🚨 {$employeeName} - On Leave TODAY");
        $event->setDescription(
            "URGENT LEAVE - Applied Today\n\n" .
            "Employee: {$employeeName}\n" .
            "From: " . Carbon::parse($startDate)->format('d M Y') . "\n" .
            "To: " . Carbon::parse($endDate)->format('d M Y') . "\n" .
            "Total Days: {$leave->total_days}\n" .
            "Reason: {$leave->reason}"
        );

        // Start = RIGHT NOW
        $start = new EventDateTime();
        $start->setDateTime($nowTime);
        $start->setTimeZone($this->timezone);
        $event->setStart($start);

        // End = 6PM today
        $end = new EventDateTime();
        $end->setDateTime($endTime);
        $end->setTimeZone($this->timezone);
        $event->setEnd($end);

        // 0 mins = fires RIGHT NOW when event is created ✅
        $popupNow = new EventReminder();
        $popupNow->setMethod('popup');
        $popupNow->setMinutes(0);

        $emailNow = new EventReminder();
        $emailNow->setMethod('email');
        $emailNow->setMinutes(0);

        $reminders = new EventReminders();
        $reminders->setUseDefault(false);
        $reminders->setOverrides([$popupNow, $emailNow]);
        $event->setReminders($reminders);

        $created = $service->events->insert($this->calendarId, $event);

        Log::info('Urgent leave event created: ' . $created->getId(), [
            'employee' => $employeeName,
            'start'    => $nowTime,
        ]);

        return $created->getId();
    }

    // FUTURE: Creates all-day event + separate 9AM reminder event
    private function createFutureLeaveEvent(
        Calendar $service,
        LeaveApplication $leave,
        string $employeeName,
        string $startDate,
        string $endDate
    ): ?string {
        // ── Part 1: All-day display event ──
        $displayEvent = new Event();
        $displayEvent->setSummary("{$employeeName} - On Leave");
        $displayEvent->setDescription(
            "Employee: {$employeeName}\n" .
            "From: " . Carbon::parse($startDate)->format('d M Y') . "\n" .
            "To: " . Carbon::parse($endDate)->format('d M Y') . "\n" .
            "Total Days: {$leave->total_days}\n" .
            "Reason: {$leave->reason}"
        );

        // All-day start
        $dStart = new EventDateTime();
        $dStart->setDate($startDate);
        $displayEvent->setStart($dStart);

        // All-day end (+1 day required by Google)
        $dEnd = new EventDateTime();
        $dEnd->setDate(
            Carbon::parse($endDate)->addDay()->format('Y-m-d')
        );
        $displayEvent->setEnd($dEnd);

        // Day before at 9AM reminder
        // All-day event midnight = reference point
        // 9AM day before = 15 hours before midnight = 15*60 = 900 mins ✅
        $dayBefore9AM = new EventReminder();
        $dayBefore9AM->setMethod('popup');
        $dayBefore9AM->setMinutes(900);

        $dayBefore9AMEmail = new EventReminder();
        $dayBefore9AMEmail->setMethod('email');
        $dayBefore9AMEmail->setMinutes(900);

        $displayReminders = new EventReminders();
        $displayReminders->setUseDefault(false);
        $displayReminders->setOverrides([
            $dayBefore9AM,
            $dayBefore9AMEmail,
        ]);
        $displayEvent->setReminders($displayReminders);

        $createdDisplay = $service->events->insert($this->calendarId, $displayEvent);
        Log::info('Display event created: ' . $createdDisplay->getId());

        // ── Part 2: Timed 9AM reminder event on leave day ──
        // This is a separate small event at 9AM
        // 0 mins before 9AM = fires exactly at 9AM ✅
        $nineAMStart = $startDate . 'T09:00:00';
        $nineAMEnd   = $startDate . 'T09:05:00';

        $reminderEvent = new Event();
        $reminderEvent->setSummary("🔔 {$employeeName} - On Leave Today");
        $reminderEvent->setDescription(
            "Reminder: {$employeeName} is on leave today.\n" .
            "Reason: {$leave->reason}"
        );

        $rStart = new EventDateTime();
        $rStart->setDateTime($nineAMStart);
        $rStart->setTimeZone($this->timezone);
        $reminderEvent->setStart($rStart);

        $rEnd = new EventDateTime();
        $rEnd->setDateTime($nineAMEnd);
        $rEnd->setTimeZone($this->timezone);
        $reminderEvent->setEnd($rEnd);

        // 0 mins before 9AM event = fires at exactly 9:00 AM ✅
        $popup9AM = new EventReminder();
        $popup9AM->setMethod('popup');
        $popup9AM->setMinutes(0);

        $email9AM = new EventReminder();
        $email9AM->setMethod('email');
        $email9AM->setMinutes(0);

        $reminderReminders = new EventReminders();
        $reminderReminders->setUseDefault(false);
        $reminderReminders->setOverrides([$popup9AM, $email9AM]);
        $reminderEvent->setReminders($reminderReminders);

        $createdReminder = $service->events->insert($this->calendarId, $reminderEvent);
        Log::info('9AM reminder event created: ' . $createdReminder->getId());

        // Return both IDs so we can delete both later
        return $createdDisplay->getId() . ',' . $createdReminder->getId();
    }

    public function updateLeaveEvent(LeaveApplication $leave): void
    {
        try {
            if (!$leave->google_event_id) return;

            // Delete old events and recreate
            $this->deleteLeaveEvent($leave);

            $newEventId = $this->createLeaveEvent($leave);
            if ($newEventId) {
                $leave->update(['google_event_id' => $newEventId]);
            }

        } catch (\Exception $e) {
            Log::error('updateLeaveEvent failed: ' . $e->getMessage());
        }
    }

    public function deleteLeaveEvent(LeaveApplication $leave): void
    {
        try {
            if (!$leave->google_event_id) return;

            $service  = $this->getService();
            // Handle both single ID and comma-separated IDs
            $eventIds = explode(',', $leave->google_event_id);

            foreach ($eventIds as $eventId) {
                $eventId = trim($eventId);
                if (!$eventId) continue;

                try {
                    $service->events->delete($this->calendarId, $eventId);
                    Log::info('Event deleted: ' . $eventId);
                } catch (\Exception $e) {
                    Log::warning('Could not delete event: ' . $eventId);
                }
            }

        } catch (\Exception $e) {
            Log::error('deleteLeaveEvent failed: ' . $e->getMessage());
        }
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function handleCallback(string $code): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        $this->client->setAccessToken($token);

        $admin = User::where('role', 'admin')->first();
        $admin->update([
            'google_token'         => $token['access_token'],
            'google_refresh_token' => $token['refresh_token']
                ?? $admin->google_refresh_token,
        ]);

        Log::info('Google token saved for: ' . $admin->email);
    }
}