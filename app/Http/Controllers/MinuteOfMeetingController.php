<?php

namespace App\Http\Controllers;
use App\Exports\MinuteOfMeetingExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\BookingMeeting;
use Maatwebsite\Excel\Facades\Excel;


class MinuteOfMeetingController extends Controller
{
    public function export($id)
    {
        return Excel::download(new MinuteOfMeetingExport($id), 'minute_of_meeting.xlsx');
    }

    public function view()
    {
        $booking = BookingMeeting::with(['minuteOfMeeting', 'visitors', 'room', 'user'])
            ->findOrFail(2);

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

public function exportPdf($id)
{
    $booking = BookingMeeting::with([
        'minuteOfMeeting',
        'visitors',
        'room',
        'user',
    ])->findOrFail($id);

    $pdf = Pdf::loadView('exports.minute_of_meeting_pdf', [
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

            // User yang buat booking
            'user' => $booking->user ? [
                'id'    => $booking->user->id,
                'name'  => $booking->user->name,
                'email' => $booking->user->email,
            ] : null,

            // ✅ Meeting With
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

            // MOM
            'minute_of_meeting' => $booking->minuteOfMeeting,
        ],
    ])->setPaper('a4', 'portrait');

    return $pdf->download('MinuteOfMeeting_'.$booking->id.'.pdf');
}

public function viewPdf($id)
{
    $booking = BookingMeeting::with([
        'minuteOfMeeting',
        'visitors',
        'room',
        'user',
    ])->findOrFail($id);

    $pdf = Pdf::loadView('exports.minute_of_meeting_pdf', [
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

            // User yang buat booking
            'user' => $booking->user ? [
                'id'    => $booking->user->id,
                'name'  => $booking->user->name,
                'email' => $booking->user->email,
            ] : null,

            // ✅ Meeting With
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

            // MOM
            'minute_of_meeting' => $booking->minuteOfMeeting,
        ],
    ])->setPaper('a4', 'portrait');

    // ✅ Stream (view langsung di browser, tanpa download otomatis)
    return $pdf->stream('MinuteOfMeeting_'.$booking->id.'.pdf');
}


}
