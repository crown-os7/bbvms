<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{

    public function index()
    {
        $departmentStats = DB::table('bookingmeetings')
            ->join('users', 'bookingmeetings.meeting_with', '=', 'users.id')
            ->select('users.departement as department', DB::raw('count(bookingmeetings.id) as total'))
            ->groupBy('users.departement')
            ->get();

        return Inertia::render('Dashboard', [
            'departmentStats' => $departmentStats
        ]);
    }

}
