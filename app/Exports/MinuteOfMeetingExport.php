<?php

namespace App\Exports;

use App\Models\BookingMeeting;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class MinuteOfMeetingExport implements FromView
{
    protected $meetingId;

    public function __construct($meetingId)
    {
        $this->meetingId = $meetingId;
    }

    public function view(): View
    {
        $booking = BookingMeeting::with(['minuteOfMeeting', 'visitors', 'room', 'user'])
            ->findOrFail($this->meetingId);

        return view('exports.minute_of_meeting', [
            'meeting' => [
                'id'          => $booking->id,
                'date'        => \Carbon\Carbon::parse($booking->date)->translatedFormat('d F Y'),
                'start_time'  => \Carbon\Carbon::parse($booking->start_time)->format('H:i'),
                'end_time'    => \Carbon\Carbon::parse($booking->end_time)->format('H:i'),
                'purpose'     => $booking->purpose,

                // Room
                'room' => $booking->room ? [
                    'id'   => $booking->room->id,
                    'name' => $booking->room->name,
                ] : null,

                // Meeting with
                'meeting_with' => $booking->user ? [
                    'id'    => $booking->user->id,
                    'name'  => $booking->user->name,
                    'email' => $booking->user->email,
                ] : null,

                // Visitors
                'visitors' => $booking->visitors->map(function ($v) {
                    return [
                        'id'      => $v->id,
                        'name'    => $v->name,
                        'company' => $v->company,
                        'status'  => $v->status,
                    ];
                })->toArray(),

                // Minute of Meeting
                'minute_of_meeting' => $booking->minuteOfMeeting,
            ],
        ]);
    }


    
}
