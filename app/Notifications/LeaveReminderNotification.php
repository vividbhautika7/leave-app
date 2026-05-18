<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class LeaveReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected array $employeeNames,
        protected string $leaveDate,
        protected string $reminderType  // 'day_before' or 'day_of'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date         = Carbon::parse($this->leaveDate)->format('d M Y hh:mm A');
        $names        = $this->employeeNames;
        $count        = count($names);
        $nameList     = implode(', ', $names);
        $isDayBefore  = $this->reminderType === 'day_before';

        // Subject line
        $subject = $isDayBefore
            ? "Reminder: {$count} Employee(s) on Leave Tomorrow ({$date})"
            : "Reminder: {$count} Employee(s) on Leave Today ({$date})";

        // Greeting line
        $greeting = $isDayBefore
            ? "Leave reminder for tomorrow — {$date}"
            : "Leave reminder for today — {$date}";

        // Build mail
        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello Admin,")
            ->line($greeting)
            ->line("---");

        // List all employees
        if ($count === 1) {
            $mail->line("**{$names[0]}** is on leave on {$date}.");
        } else {
            $mail->line("The following **{$count} employees** are on leave on {$date}:");
            foreach ($names as $name) {
                $mail->line("• {$name}");
            }
        }

        $mail->action('View Admin Dashboard', url('/admin/dashboard'))
             ->line('This is an automated reminder from the Leave Management System.');

        return $mail;
    }
}