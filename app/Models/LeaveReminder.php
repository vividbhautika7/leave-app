<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'reminder_date',
        'leave_date',
        'sent',
    ];

    protected $casts = [
        'reminder_date' => 'date',
        'leave_date'    => 'date',
        'sent'          => 'boolean',
    ];

    // Scope: unsent reminders
    public function scopeUnsent($query)
    {
        return $query->where('sent', false);
    }

    // Scope: reminders due today
    public function scopeDueToday($query)
    {
        return $query->where('reminder_date', today());
    }
}