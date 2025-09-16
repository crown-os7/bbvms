<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'imgroom',
        'capacity',
        'facility',
        'description'
    ];

    /**
     * Relasi ke BookingMeeting
     */
    public function bookings()
    {
        return $this->hasMany(BookingMeeting::class);
    }
}
