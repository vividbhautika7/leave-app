<?php

namespace App\Console\Commands;

use App\Services\GoogleCalendarService;
use App\Models\LeaveApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClearAllLeaves extends Command
{
    protected $signature   = 'leaves:clear';
    protected $description = 'Delete all leaves from database and Google Calendar';

    public function handle(): void
    {
        $service = app(GoogleCalendarService::class);

        // Use DB directly
        $leaves = DB::table('leave_applications')
                    ->join('users', 'leave_applications.user_id', '=', 'users.id')
                    ->select(
                        'leave_applications.id',
                        'leave_applications.google_event_id',
                        'leave_applications.start_date',
                        'leave_applications.end_date',
                        'users.name as user_name'
                    )
                    ->get();

        if ($leaves->isEmpty()) {
            $this->info('No leaves found.');
            return;
        }

        $this->info("Found {$leaves->count()} leave(s). Starting cleanup...");
        $this->newLine();

        foreach ($leaves as $row) {

            $startDate = Carbon::parse($row->start_date)->format('Y-m-d');
            $today     = Carbon::today()->format('Y-m-d');
            $isUrgent  = ($startDate === $today);

            // Delete Google Calendar event(s)
            if ($row->google_event_id) {
                try {
                    // Handle both single ID and comma-separated IDs
                    // Urgent leave = single event ID
                    // Future leave = two IDs separated by comma (display + 9AM reminder)
                    $eventIds = explode(',', $row->google_event_id);

                    foreach ($eventIds as $eventId) {
                        $eventId = trim($eventId);
                        if (!$eventId) continue;

                        $leave                  = new LeaveApplication();
                        $leave->google_event_id = $eventId;
                        $service->deleteLeaveEvent($leave);

                        $type = $isUrgent ? 'Urgent' : 'Future';
                        $this->info("🗑  Google Calendar deleted [{$type}] — {$row->user_name} (Event: {$eventId})");
                    }

                } catch (\Exception $e) {
                    $this->warn("⚠️  Calendar delete failed — {$row->user_name}: " . $e->getMessage());
                }
            } else {
                $this->warn("⚠️  No Calendar event — {$row->user_name}");
            }

            // Delete reminders
            $start   = Carbon::parse($row->start_date);
            $end     = Carbon::parse($row->end_date);
            $current = $start->copy();

            while ($current->lte($end)) {
                DB::table('leave_reminders')
                    ->where('leave_date', $current->format('Y-m-d'))
                    ->delete();
                $current->addDay();
            }

            // Hard delete from DB
            DB::table('leave_applications')
                ->where('id', $row->id)
                ->delete();

            $this->info("✅ Deleted — {$row->user_name} (ID: {$row->id})");
            $this->newLine();
        }

        // Clear all remaining reminders
        DB::table('leave_reminders')->truncate();
        $this->info("🗑  All reminders cleared.");
        $this->newLine();
        $this->info("✅ All leaves cleared from DB and Google Calendar!");
    }
}