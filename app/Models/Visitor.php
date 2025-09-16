<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'position',
        'email',
        'phone',
        'photo',
        'status',
        'booking_meeting_id',
    ];

    public function bookingMeeting()
    {
        return $this->belongsTo(BookingMeeting::class, 'booking_meeting_id');
    }
}
