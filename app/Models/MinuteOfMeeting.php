<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinuteOfMeeting extends Model
{
    protected $fillable = ['booking_meeting_id', 'details'];

    public function bookingMeeting()
    {
        return $this->belongsTo(BookingMeeting::class);
    }
}

