<?php

namespace App\Console\Commands;

use App\Models\BookingMeeting;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ClearOldReferralCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:clear-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear referral codes from closed bookings older than 1 week';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = BookingMeeting::where('status', 'closed')
            ->whereNotNull('referral_code')
            ->whereDate('updated_at', '<=', Carbon::now()->subWeek())
            ->update(['referral_code' => '-']);

        $this->info("✅ Cleared referral codes for {$count} closed bookings older than 1 week.");
    }
}
