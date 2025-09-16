<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BookingMeeting;
use Carbon\Carbon;

class BookingmeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
        public function run(): void
    {
        // Booking closed 8 hari lalu (harus terhapus referral_code)
        BookingMeeting::create([
            'room_id'       => 1,
            'meeting_with'  => 1,
            'name'          => 'Dummy Visitor', // 👈 WAJIB ADA
            'position'      => 'Staff', // 👈 WAJIB ADA
            'company'      => 'Bima', // 👈 WAJIB ADA
            'date'          => Carbon::now()->subDays(8)->format('Y-m-d'),
            'start_time'    => '09:00',
            'end_time'      => '10:00',
            'duration'      => 60,
            'purpose'       => 'Test old closed meeting',
            'status'        => 'closed',
            'referral_code' => 'OLD123',
            'created_by'    => 1,
            'created_at'    => Carbon::now()->subDays(8),
            'updated_at'    => Carbon::now()->subDays(8),
        ]);


        // Booking closed 2 hari lalu (tidak boleh kehapus referral_code)
        BookingMeeting::create([
            'room_id'       => 1,
            'meeting_with'  => 1,
            'name'          => 'Another Visitor', // 👈 WAJIB ADA
            'position'      => 'Manager', // 👈 WAJIB ADA
            'company'      => 'Alloy', // 👈 WAJIB ADA
            'date'          => Carbon::now()->subDays(2)->format('Y-m-d'),
            'start_time'    => '11:00',
            'end_time'      => '12:00',
            'duration'      => 60,
            'purpose'       => 'Test recent closed meeting',
            'status'        => 'closed',
            'referral_code' => 'NEW456',
            'created_by'    => 1,
            'created_at'    => Carbon::now()->subDays(2),
            'updated_at'    => Carbon::now()->subDays(2),
        ]);

    }
}
