<?php

namespace App\Console\Commands;

use App\Models\LeaveApplication;
use App\Models\LeaveReminder;
use App\Models\User;
use App\Notifications\LeaveReminderNotification;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventReminder;
use Google\Service\Calendar\EventReminders;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendLeaveReminders extends Command
{
    protected $signature   = 'reminders:send';
    protected $description = 'Send grouped leave reminder emails + Google Calendar notifications to admin at 9AM';

    public function handle(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        $this->info("Running leave reminders for: {$today}");

        // Get all unsent reminders due today
        $dueReminders = LeaveReminder::where('reminder_date', $today)
                            ->where('sent', false)
                            ->get();

        if ($dueReminders->isEmpty()) {
            $this->info('No reminders due today.');
            return;
        }

        // Group reminders by leave_date
        $grouped = $dueReminders->groupBy('leave_date');

        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            $this->error('No admin user found.');
            return;
        }

        foreach ($grouped as $leaveDate => $reminders) {

            // Find all employees on leave on this specific date
            $leavesOnDate = LeaveApplication::where('start_date', '<=', $leaveDate)
                                ->where('end_date', '>=', $leaveDate)
                                ->with('user')
                                ->get();

            if ($leavesOnDate->isEmpty()) {
                foreach ($reminders as $reminder) {
                    $reminder->update(['sent' => true]);
                }
                continue;
            }

            // Collect employee names
            $employeeNames = $leavesOnDate->pluck('user.name')->toArray();
            $nameList      = implode(', ', $employeeNames);
            $count         = count($employeeNames);

            // Determine reminder type
            $reminderType  = ($leaveDate === $today) ? 'day_of' : 'day_before';
            $formattedDate = Carbon::parse($leaveDate)->format('d M Y');

            // Step 1 — Send email notification
            try {
                $admin->notify(
                    new LeaveReminderNotification(
                        $employeeNames,
                        $leaveDate,
                        $reminderType
                    )
                );

                $this->info("✅ Email sent for {$leaveDate}: {$nameList}");

                Log::info("Leave reminder email sent", [
                    'leave_date'    => $leaveDate,
                    'reminder_type' => $reminderType,
                    'employees'     => $employeeNames,
                ]);

            } catch (\Exception $e) {
                $this->error("❌ Email failed for {$leaveDate}: " . $e->getMessage());
                Log::error("Leave reminder email failed", [
                    'leave_date' => $leaveDate,
                    'error'      => $e->getMessage(),
                ]);
            }

            // Step 2 — Send Google Calendar notification
            try {
                $this->sendCalendarNotification(
                    $employeeNames,
                    $leaveDate,
                    $formattedDate,
                    $reminderType,
                    $count
                );

                $this->info("✅ Calendar notification sent for {$leaveDate}");

            } catch (\Exception $e) {
                $this->error("❌ Calendar notification failed for {$leaveDate}: " . $e->getMessage());
                Log::error("Leave reminder calendar notification failed", [
                    'leave_date' => $leaveDate,
                    'error'      => $e->getMessage(),
                ]);
            }

            // Mark all reminders as sent
            foreach ($reminders as $reminder) {
                $reminder->update(['sent' => true]);
            }
        }

        $this->info('All reminders processed successfully.');
    }

    // Send immediate Google Calendar notification
    private function sendCalendarNotification(
        array $employeeNames,
        string $leaveDate,
        string $formattedDate,
        string $reminderType,
        int $count
    ): void {
        $googleService = app(GoogleCalendarService::class);

        // Get calendar service via reflection to access protected client
        $reflection = new \ReflectionClass($googleService);

        $clientProp = $reflection->getProperty('client');
        $clientProp->setAccessible(true);
        $client = $clientProp->getValue($googleService);

        $calendarIdProp = $reflection->getProperty('calendarId');
        $calendarIdProp->setAccessible(true);
        $calendarId = $calendarIdProp->getValue($googleService);

        $timezoneProp = $reflection->getProperty('timezone');
        $timezoneProp->setAccessible(true);
        $timezone = $timezoneProp->getValue($googleService);

        $service   = new \Google\Service\Calendar($client);
        $nameList  = implode(', ', $employeeNames);
        $nowTime   = Carbon::now($timezone)->format('Y-m-d\TH:i:s');
        $nowPlus5  = Carbon::now($timezone)->addMinutes(5)->format('Y-m-d\TH:i:s');

        // Build title based on type and count
        if ($reminderType === 'day_before') {
            $title = $count === 1
                ? "📅 Tomorrow: {$nameList} - On Leave ({$formattedDate})"
                : "📅 Tomorrow: {$count} Employees On Leave ({$formattedDate})";
        } else {
            $title = $count === 1
                ? "🔔 Today: {$nameList} - On Leave ({$formattedDate})"
                : "🔔 Today: {$count} Employees On Leave ({$formattedDate})";
        }

        // Build description with all employee names
        $description = $reminderType === 'day_before'
            ? "Leave Reminder — Tomorrow ({$formattedDate})\n\n"
            : "Leave Reminder — Today ({$formattedDate})\n\n";

        if ($count === 1) {
            $description .= "{$nameList} is on leave.";
        } else {
            $description .= "The following {$count} employees are on leave:\n";
            foreach ($employeeNames as $name) {
                $description .= "• {$name}\n";
            }
        }

        // Create event starting RIGHT NOW with 0 min reminder
        // This fires notification immediately on Google Calendar app
        $event = new Event();
        $event->setSummary($title);
        $event->setDescription($description);

        // Start = NOW
        $start = new EventDateTime();
        $start->setDateTime($nowTime);
        $start->setTimeZone($timezone);
        $event->setStart($start);

        // End = 5 mins from now (short event)
        $end = new EventDateTime();
        $end->setDateTime($nowPlus5);
        $end->setTimeZone($timezone);
        $event->setEnd($end);

        // 0 mins = fires immediately ✅
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

        $created = $service->events->insert($calendarId, $event);

        Log::info('Calendar reminder notification created: ' . $created->getId(), [
            'leave_date'    => $leaveDate,
            'reminder_type' => $reminderType,
            'employees'     => $employeeNames,
            'title'         => $title,
        ]);
    }
}