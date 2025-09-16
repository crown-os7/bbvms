<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingMeeting extends Model
{
    use HasFactory;

    protected $table = 'bookingmeetings';

    protected $fillable = [
        'room_id',
        'name',
        'position',
        'company',
        'date',
        'start_time',
        'end_time',
        'duration',
        'purpose',
        'meeting_with',
        'referral_code', 
        'status',
        'created_by',  
    ];
    /**
     * Relasi ke model Room
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    
    public function visitors()
    {
        return $this->hasMany(Visitor::class, 'booking_meeting_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'meeting_with'); 
    }
        public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function minuteOfMeeting()
    {
        return $this->hasOne(MinuteOfMeeting::class);
    }
}
