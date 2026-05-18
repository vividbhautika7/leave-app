<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'google_event_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'deleted_at' => 'datetime',
    ];


    

    // Each leave belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Calculate total number of leave days
    public function getTotalDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    // Scope: leaves that overlap with a given date
    public function scopeOnDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)
                     ->where('end_date', '>=', $date);
    }
}