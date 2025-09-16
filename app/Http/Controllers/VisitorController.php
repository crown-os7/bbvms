<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BookingMeeting;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VisitorController extends Controller
{
    /**
     * ✅ Form Check-In
     */
    public function indexCheckIn()
    {
        return Inertia::render('VisitorForm/CheckIn');
    }

    /**
     * ✅ Form Check-Out
     */
    public function indexCheckOut()
    {
        return Inertia::render('VisitorForm/CheckOut');
    }

    /**
     * ✅ Check-in via form sederhana
     */
    public function store(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|string|exists:booking_meetings,referral_code',
            'name'          => 'required|string|max:255',
        ]);

        $booking = BookingMeeting::where('referral_code', $request->referral_code)->first();

        if (!$booking) {
            return back()->withErrors(['referral_code' => 'The referral code is invalid.']);
        }

        if ($booking->status !== 'open') {
            return back()->withErrors(['referral_code' => 'The booking has been canceled/closed, so you cannot check in.']);
        }

        // Ubah status booking menjadi in_progress
        $booking->update(['status' => 'in_progress']);

        // Simpan visitor baru
        Visitor::create([
            'booking_meeting_id' => $booking->id,
            'name'               => $request->name,
        ]);

        return redirect()->route('visitors.create')->with('success', 'Check-in successful');
    }

    /**
     * ✅ Ambil data booking by referral
     */
public function getByReferral(Request $request)
{
    $code = $request->input('referral_code');

    $booking = BookingMeeting::where('referral_code', $code)
                ->with('visitors')
                ->first();

    if (!$booking) {
        return response()->json(['message' => 'Referral code not found'], 404);
    }

    return response()->json([
        'booking_id' => $booking->id,
        'visitors'   => $booking->visitors->map(function ($v) use ($booking) {
            return [
                'id'         => $v->id,
                'name'       => $v->name,
                'company'    => $v->company,
                'position'   => $v->position,
                'email'      => $v->email,
                'phone'      => $v->phone,
                'photo'      => $v->photo,
                'status'     => $v->status,      // ✅ tambahkan status visitor
                'booking_id' => $booking->id,    // ✅ sertakan booking id
            ];
        }),
    ]);
}


    /**
     * ✅ Check-in lengkap (pakai kamera, form detail)
     */
public function checkIn(Request $request)
{
    $request->validate([
        'referral_code' => 'required|string',
        'name'          => 'required|string|max:255',
        'company'       => 'nullable|string|max:255',
        'position'      => 'required|string|max:255',
        'email'         => 'required|email|max:255',
        'phone'         => 'required|string|max:20',
        'photo'         => 'required|string',
        'visitors_id'   => 'nullable|integer'
    ]);

    $booking = BookingMeeting::where('referral_code', $request->referral_code)->firstOrFail();

    if (in_array($booking->status, ['closed', 'cancelled'])) {
        return response()->json([
            'message' => 'The booking has been canceled/closed, so you cant check in anymore.'
        ], 422);
    }

    if ($booking->status === 'open') {
        $booking->update(['status' => 'in_progress']);
    }

    // ✅ simpan foto
    $photoPath = null;
    if ($request->photo) {
        $image = preg_replace('/^data:image\/\w+;base64,/', '', $request->photo);
        $image = str_replace(' ', '+', $image);
        $imageName = 'visitors_' . time() . '.png';
        $path = public_path('img/visitor/' . $imageName);
        if (!file_exists(dirname($path))) mkdir(dirname($path), 0755, true);
        file_put_contents($path, base64_decode($image));
        $photoPath = $imageName;
    }

    if ($request->visitors_id) {
        // 🔥 pilih dari list
        $visitor = Visitor::findOrFail($request->visitors_id);

        // ✅ validasi check-in ganda
        if ($visitor->status === 'checked-in') {
            return response()->json([
                'message' => 'This visitor has already checked in and cannot check in twice.'
            ], 422);
        }

        $visitor->update([
            'company'  => $request->company,
            'position' => $request->position,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'photo'    => $photoPath ?? $visitor->photo,
            'status'   => 'checked-in', // ✅ update status
        ]);
    } else {
        // 🔥 manual create baru
        // ✅ validasi nama yang sama di booking
        $existingVisitor = $booking->visitors()
            ->where('name', $request->name)
            ->where('status', 'checked-in')
            ->first();

        if ($existingVisitor) {
            return response()->json([
                'message' => 'This visitor has already checked in previously and cannot check in twice.'
            ], 422);
        }

        $visitor = Visitor::create([
            'booking_meeting_id' => $booking->id,
            'name'      => $request->name,
            'company'   => $request->company,
            'position'  => $request->position,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'photo'     => $photoPath,
            'status'    => 'checked-in', // ✅ set status saat create
        ]);
    }

    return response()->json([
        'message' => 'Check-in successful',
        'booking' => $booking->load('visitors'),
        'visitor' => $visitor,
    ]);
}




    /**
     * ✅ Check-out visitor
     */
public function checkout(Request $request)
{
    $request->validate([
        'referral_code' => 'required|string|size:6',
        'visitor_id' => 'required|integer|exists:visitors,id',
    ]);

    $booking = BookingMeeting::where('referral_code', $request->referral_code)
                ->with('visitors')
                ->first();

    if (!$booking) {
        return back()->withErrors(['referral_code' => 'Referral code not found.']);
    }

    // ✅ Cek visitor yang dipilih termasuk di booking ini
    $visitor = $booking->visitors()->where('id', $request->visitor_id)->first();
    if (!$visitor) {
        return back()->withErrors(['visitor_id' => 'No visitors were found in this booking.']);
    }

    if ($visitor->status !== 'checked-in') {
        return back()->withErrors(['visitor_id' => 'This visitor has not checked in or has already checked out.']);
    }

    // ✅ Update status visitor yang dipilih
    $visitor->update(['status' => 'checked-out']);

    // ✅ Cek semua visitor yang statusnya checked-in
    $checkedInCount = $booking->visitors()->where('status', 'checked-in')->count();

    if ($checkedInCount === 0) {
        // ✅ Semua visitor yang checked-in sudah checkout → close booking
        $booking->update(['status' => 'closed']);

        // ✅ Update visitor yang masih pending menjadi cancelled
        $booking->visitors()->where('status', 'pending')->update(['status' => 'cancelled']);
    }

    return back()->with('success', "Checkout successful . Visitor {$visitor->name} has been updated to checked-out.");
}

public function print(Request $request)
{
    $bookingId  = $request->booking_id;
    $visitorId  = $request->visitor_id; // ambil juga visitor_id

    $booking = BookingMeeting::with(['room', 'visitors', 'user', 'creator'])
                ->findOrFail($bookingId);

    return Inertia::render('VisitorForm/Print', [
        'booking' => [
            'id'           => $booking->id,
            'date'         => \Carbon\Carbon::parse($booking->date)->format('d M'),
            'start_time'   => \Carbon\Carbon::parse($booking->start_time)->format('h:i A'),
            'end_time'     => \Carbon\Carbon::parse($booking->end_time)->format('h:i A'),
            'purpose'      => $booking->purpose,
            'status'       => $booking->status,
            'room' => $booking->room ? [
                'id'   => $booking->room->id,
                'name' => $booking->room->name,
            ] : null,
            'meeting_with' => $booking->user ? $booking->user->name : null,
            'visitors' => $booking->visitors->map(fn($v) => [
                'id'       => $v->id,
                'name'     => $v->name,
                'company'  => $v->company,
                'position' => $v->position, 
                'status'   => $v->status,
                'photo'    => $v->photo
                                ? asset('img/visitor/' . $v->photo)
                                : asset('img/default/logo-bb.png'),
            ]),
        ],
        'visitor_id' => $visitorId, // kirim ke props
    ]);
}



public function detail($bookingId, $visitorId)
{
    $booking = BookingMeeting::with(['room', 'visitors', 'user'])->findOrFail($bookingId);
    $visitor = $booking->visitors()->findOrFail($visitorId);

    return Inertia::render('VisitorForm/Detail', [
        'booking' => [
            'id'         => $booking->id,
            'date'       => \Carbon\Carbon::parse($booking->date)->format('d M'),
            'start_time' => \Carbon\Carbon::parse($booking->start_time)->format('h:i A'),
            'end_time'   => \Carbon\Carbon::parse($booking->end_time)->format('h:i A'),
            'purpose'    => $booking->purpose,
            'status'     => $booking->status,
            'room'       => $booking->room ? [
                'id'   => $booking->room->id,
                'name' => $booking->room->name,
            ] : null,
            'meeting_with' => $booking->user?->name,
        ],
        'visitor' => [
            'id'       => $visitor->id,
            'name'     => $visitor->name,
            'email'     => $visitor->email,
            'company'  => $visitor->company,
            'position' => $visitor->position,
            'status'   => $visitor->status,
            'photo'    => $visitor->photo
                            ? asset('img/visitor/' . $visitor->photo)
                            : asset('img/default/logo-bb.png'),
        ],
    ]);
}




}
