<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveAppliedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected LeaveApplication $leave
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employee   = $this->leave->user->name;
        $startDate  = $this->leave->start_date->format('d M Y');
        $endDate    = $this->leave->end_date->format('d M Y');
        $totalDays  = $this->leave->total_days;
        $reason     = $this->leave->reason;
        $isToday   = $this->leave->start_date->isToday();

        return (new MailMessage)
            ->subject(
                $isToday
                    ? "🚨 URGENT: {$employee} Applied Leave for TODAY"
                    : "Leave Application - {$employee}"
            )
            ->greeting("Hello Admin,")
            ->line(
                $isToday
                    ? "⚠️ {$employee} has applied for leave starting TODAY."
                    : "{$employee} has applied for leave."
            )
            ->line("**From:** {$startDate}")
            ->line("**To:** {$endDate}")
            ->line("**Total Days:** {$totalDays}")
            ->line("**Reason:** {$reason}")
            ->action('View Dashboard', url('/admin/dashboard'))
            ->line(
                $isToday
                    ? "This is an urgent leave starting today!"
                    : "Please review the leave application."
            );

        // return (new MailMessage)
        //     ->subject("Leave Application - {$employee}")
        //     ->greeting("Hello Admin,")
        //     ->line("{$employee} has applied for leave.")
        //     ->line("**From:** {$startDate}")
        //     ->line("**To:** {$endDate}")
        //     ->line("**Total Days:** {$totalDays}")
        //     ->line("**Reason:** {$reason}")
        //     ->action('View Dashboard', url('/admin/dashboard'))
        //     ->line('Please review the leave application.');
    }
}