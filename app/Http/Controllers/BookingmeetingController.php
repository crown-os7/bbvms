<?php

namespace App\Http\Controllers;

use App\Models\BookingMeeting;
use App\Models\MinuteOfMeeting;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class BookingMeetingController extends Controller
{
    public function index()
    {
        return Inertia::render('BookingMeeting/Index', [
            'bookingmeeting' => BookingMeeting::with(['room', 'visitors', 'user', 'creator','minuteOfMeeting'])
                ->get()
                ->map(function ($item) {
                    $firstVisitor = $item->visitors->first();
                    $user    = optional($item->user);
                    $creator = optional($item->creator);

                    return [
                        'id'           => $item->id,
                        'date'         => $item->date,
                        'start_time'   => $item->start_time,
                        'end_time'     => $item->end_time,
                        'duration'     => $item->duration,
                        'purpose'      => $item->purpose,
                        'referral_code'=> $item->referral_code,
                        'status'       => $item->status,

                        // Room
                        'room' => $item->room ? [
                            'id'      => $item->room->id,
                            'name'    => $item->room->name,
                            'imgroom' => $item->room->imgroom,
                        ] : null,

                        // Visitor pertama
                        'visitor_first' => [
                            'name'     => $firstVisitor->name     ?? '-',
                            'company'  => $firstVisitor->company  ?? '-',
                            'position' => $firstVisitor->position ?? '-',
                            'email'    => $firstVisitor->email    ?? '-',
                            'phone'    => $firstVisitor->phone    ?? '-',
                            'status'   => $firstVisitor->status   ?? '-',
                            'photo'    => $firstVisitor && $firstVisitor->photo
                                            ? asset('img/visitor/' . $firstVisitor->photo)
                                            : asset('img/default/logo-bb.png'),
                        ],

                        // Semua visitors
                        'visitors' => $item->visitors->map(function($v) {
                            return [
                                'id'       => $v->id,
                                'name'     => $v->name ?? '-',
                                'company'  => $v->company ?? '-',
                                'position' => $v->position ?? '-',
                                'email'    => $v->email ?? '-',
                                'phone'    => $v->phone ?? '-',
                                'status'   => $v->status ?? '-',
                                'photo'    => $v->photo 
                                                ? asset('img/visitor/' . $v->photo) 
                                                : asset('img/default/logo-bb.png'),
                            ];
                        })->values()->toArray(), // ✅ ubah visitors ke array

                        // Meeting With
                        'meeting_with' => $user ? [
                            'id'    => $user->id,
                            'name'  => $user->name,
                            'email' => $user->email,
                        ] : null,

                        // Created By
                        'created_by' => $creator ? [
                            'id'    => $creator->id,
                            'name'  => $creator->name,
                            'email' => $creator->email,
                        ] : null,

                        // Minute of Meeting
                        'minute_of_meeting' => $item->minuteOfMeeting ? [
                            'id'      => $item->minuteOfMeeting->id,
                            'details' => $item->minuteOfMeeting->details,
                        ] : null,
                    ];
                })
                ->values() // ✅ reset index collection
                ->toArray(), // ✅ convert ke array JS
        ]);
    }



    public function checkRoomStatus(Request $request)
{
    try {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:1',
        ]);

        $date = $validated['date'];
        $start_time = $validated['start_time'];
        $duration = (int) $validated['duration'];
        $bookingId = $request->booking_id ?? null;

        $start = \Carbon\Carbon::createFromFormat('H:i', $start_time);
        $end   = (clone $start)->addMinutes($duration);

        // ✅ hanya ambil booking aktif (status bukan cancelled)
        $bookings = BookingMeeting::where('date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($bookingId, fn($q) => $q->where('id', '!=', $bookingId))
            ->get();

        $rooms = Room::all();

        // relasi antar ruang
        $relations = [
            3 => [1, 2], // Nakula & Sadewa blok Nakula dan Sadewa
            1 => [3],    // Nakula blok Nakula & Sadewa
            2 => [3],    // Sadewa blok Nakula & Sadewa
        ];

        $availableRooms = $rooms->filter(function ($room) use ($bookings, $start, $end, $relations) {
            foreach ($bookings as $b) {
                $bookedStart = \Carbon\Carbon::parse($b->start_time);
                $bookedEnd   = (clone $bookedStart)->addMinutes((int) $b->duration);

                // cek overlap waktu
                if ($start < $bookedEnd && $end > $bookedStart) {
                    $bookedRoomId = $b->room_id;

                    // cek ruangan yang sama
                    if ($room->id === $bookedRoomId) {
                        return false;
                    }

                    // cek relasi antar ruang
                    if (isset($relations[$room->id]) && in_array($bookedRoomId, $relations[$room->id])) {
                        return false;
                    }

                    if (isset($relations[$bookedRoomId]) && in_array($room->id, $relations[$bookedRoomId])) {
                        return false;
                    }
                }
            }
            return true;
        });

        return response()->json([
            'availableRooms' => $availableRooms->values()->toArray(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Failed to check room status',
            'message' => $e->getMessage(),
        ], 500);
    }
}


    public function create()
    {
        $user = Auth::user();

        return Inertia::render('BookingMeeting/Create', [
            'rooms' => Room::select(['id', 'name', 'imgroom', 'capacity', 'facility'])->get(),
            'employees' => in_array($user->role, ['admin', 'security'])
                ? User::where('role', 'employee')->select('id', 'name')->get()
                : [],
            'auth' => [
                'user' => $user
            ],
        ]);
    }

    private function generateReferralCode()
    {
        do {
            $letters = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3));
            $numbers = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
            $code = $letters . $numbers;
        } while (BookingMeeting::where('referral_code', $code)->exists());

        return $code;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'visitors' => 'required|array|min:1',
            'visitors.*.name' => 'required|string|max:255',
            'visitors.*.position' => 'nullable|string|max:255',
            'visitors.*.company' => 'nullable|string|max:255',
            'visitors.*.email' => 'nullable|email',
            'visitors.*.phone' => 'nullable|string',
            'visitors.*.status' => 'nullable|in:pending,checked-in,checked-out,cancelled',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:1|max:480',
            'purpose' => 'nullable|string|max:255',
            'meeting_with' => 'required|exists:users,id',
        ]);

        $start = Carbon::createFromFormat('H:i', $validated['start_time']);
        $end   = (clone $start)->addMinutes($validated['duration']);

        $conflict = BookingMeeting::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->where('status', '!=', 'cancelled') 
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                ->orWhereBetween('end_time', [$start, $end])
                ->orWhere(function ($q2) use ($start, $end) {
                    $q2->where('start_time', '<', $start)
                        ->where('end_time', '>', $end);
                });
            })
            ->exists();


        if ($conflict) {
            return back()
                ->withInput()
                ->withErrors(['room_id' => 'This room is already booked at that time.']);
        }

        $booking = BookingMeeting::create([
            'room_id' => $validated['room_id'],
            'name' => $validated['visitors'][0]['name'],
            'position' => '',
            'company' => '',
            'date' => $validated['date'],
            'start_time' => $start->format('H:i'),
            'end_time' => $end->format('H:i'),
            'duration' => $validated['duration'],
            'purpose' => $validated['purpose'] ?? null,
            'meeting_with' => $validated['meeting_with'],
            'status' => 'open',
            'referral_code' => $this->generateReferralCode(),
            'created_by' => Auth::id(),
        ]);

        foreach ($validated['visitors'] as $v) {
            $booking->visitors()->create([
                'name' => $v['name'],
                'position' => $v['position'] ?? null,
                'company' => $v['company'] ?? null,
                'email' => $v['email'] ?? null,
                'phone' => $v['phone'] ?? null,
                'status' => $v['status'] ?? 'pending',
            ]);
        }

        return redirect()->route('bookingmeeting.create')
            ->with('success', 'The meeting booking has been successfully created.')
            ->with('referral_code', $booking->referral_code);
    }

    public function edit(BookingMeeting $bookingmeeting)
    {
        $user = Auth::user();

        if ($user && $user->role === 'security') {
            abort(403, 'Security is not allowed to access this page.');
        }

        if ($bookingmeeting->status !== 'open') {
            return redirect()->route('bookingmeeting.index')
                ->withErrors(['status' => 'Bookings cannot be edited.']);
        }

        return Inertia::render('BookingMeeting/Edit', [
            'booking' => [
                'id' => $bookingmeeting->id,
                'room_id' => $bookingmeeting->room_id,
                'meeting_with' => $bookingmeeting->meeting_with,
                'date' => \Carbon\Carbon::parse($bookingmeeting->date)->format('Y-m-d'),
                'start_time' => \Carbon\Carbon::parse($bookingmeeting->start_time)->format('H:i'),
                'duration' => $bookingmeeting->duration,
                'purpose' => $bookingmeeting->purpose,
                'visitors' => $bookingmeeting->visitors()->get(['id','name','position','company']),
            ],
            'rooms' => Room::select('id','name','imgroom','capacity','facility')->get(),
            'employees' => User::select('id','name','email')->get(),
        ]);
    }

    public function update(Request $request, BookingMeeting $bookingmeeting)
    {
        if ($bookingmeeting->status !== 'open') {
            return back()->withErrors(['status' => 'Booking cannot be changed.']);
        }

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'meeting_with' => 'required|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:1',
            'purpose' => 'nullable|string',
            'visitors' => 'required|array|min:1',
            'visitors.*.name' => 'required|string',
            'visitors.*.company' => 'nullable|string',
            'visitors.*.position' => 'nullable|string',
            'visitors.*.email' => 'nullable|email',
            'visitors.*.phone' => 'nullable|string',
            'visitors.*.status' => 'nullable|in:pending,checked-in,checked-out,cancelled',
        ]);

        $start = Carbon::createFromFormat('H:i', $validated['start_time']);
        $end   = (clone $start)->addMinutes($validated['duration']);

        $bookingmeeting->update([
            'room_id' => $validated['room_id'],
            'meeting_with' => $validated['meeting_with'],
            'date' => $validated['date'],
            'start_time' => $start->format('H:i'),
            'end_time' => $end->format('H:i'),
            'duration' => $validated['duration'],
            'purpose' => $validated['purpose'],
        ]);

        $bookingmeeting->visitors()->delete();
        foreach ($validated['visitors'] as $v) {
            $bookingmeeting->visitors()->create($v);
        }

        return redirect()->route('bookingmeeting.index')
            ->with('success', 'Booking successfully updated.');
    }

    public function cancel($id)
    {
        $booking = BookingMeeting::findOrFail($id);

        if ($booking->status !== 'open') {
            return redirect()->route('bookingmeeting.index')
                ->withErrors(['status' => 'Booking cannot be canceled.']);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return redirect()->route('bookingmeeting.index')
            ->with('success', 'Booking has been successfully canceled.');
    }

public function minute($id)
{
    $booking = BookingMeeting::with(['minuteOfMeeting', 'visitors', 'room', 'user'])->findOrFail($id);

    return Inertia::render('BookingMeeting/MinuteOfMeeting', [
        'meeting' => [
            'id'    => $booking->id,
            'date' => \Carbon\Carbon::parse($booking->date)->translatedFormat('d F Y'),
            'start_time' => \Carbon\Carbon::parse($booking->start_time)->format('H:i'),
            'end_time'   => \Carbon\Carbon::parse($booking->end_time)->format('H:i'),
            'purpose'    => $booking->purpose,
            
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
            }),
        ],
        'minuteofmeeting' => $booking->minuteOfMeeting,
    ]);
}




    public function storeMinute(Request $request, $id)
    {
        $request->validate([
            'details' => 'required|string'
        ]);

        $booking = BookingMeeting::findOrFail($id);

        MinuteOfMeeting::updateOrCreate(
            ['booking_meeting_id' => $booking->id],
            ['details' => $request->details]
        );

        return redirect()->route('bookingmeeting.index')->with('success', 'Minutes of Meeting saved.');
    }

public function exportSelectedPdf(Request $request)
{
    $ids = $request->input('ids', []);

    $bookings = BookingMeeting::with([
        'minuteOfMeeting',
        'visitors',
        'room',
        'user',
    ])
    ->whereIn('id', $ids)
    ->where('status', 'closed')
    ->get();

    if ($bookings->isEmpty()) {
        return response()->json(['error' => 'No closed data selected.'], 422);
    }

    $meetings = $bookings->map(function ($booking) {
        return [
            'id' => $booking->id,
            'date' => $booking->date ? \Carbon\Carbon::parse($booking->date)->translatedFormat('d F Y') : '-',
            'start_time' => $booking->start_time ? \Carbon\Carbon::parse($booking->start_time)->format('H:i') : '-',
            'end_time' => $booking->end_time ? \Carbon\Carbon::parse($booking->end_time)->format('H:i') : '-',
            'purpose' => $booking->purpose ?? '-',

            'room' => $booking->room ? [
                'id' => $booking->room->id,
                'name' => $booking->room->name ?? '-',
            ] : null,

            'user' => $booking->user ? [
                'id' => $booking->user->id,
                'name' => $booking->user->name ?? '-',
                'email' => $booking->user->email ?? '-',
            ] : null,

            'meeting_with' => $booking->user ? [
                'id' => $booking->user->id,
                'name' => $booking->user->name ?? '-',
                'email' => $booking->user->email ?? '-',
            ] : null,

            'visitors' => $booking->visitors->map(function ($v) {
                return [
                    'id' => $v->id,
                    'name' => $v->name ?? '-',
                    'company' => $v->company ?? '-',
                    'status' => $v->status ?? '-',
                ];
            })->toArray(),

            'minute_of_meeting' => $booking->minuteOfMeeting
                ? ['details' => $booking->minuteOfMeeting->details ?? '-']
                : ['details' => '-'],
        ];
    })->toArray();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.minute_of_meeting_bulk_pdf', [
        'meetings' => $meetings
    ])->setPaper('a4', 'portrait');

    return $pdf->download('MinuteOfMeeting_Selected.pdf');
}





    public function destroy(BookingMeeting $bookingMeeting)
    {
        $bookingMeeting->delete();

        return redirect()->back()->with('success', 'Booking meeting successfully deleted.');
    }


}
