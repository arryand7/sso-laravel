<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the portal dashboard with accessible applications.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $applications = $user->accessibleApplications();
        
        // Group applications by category
        $groupedApps = $applications->groupBy('category');

        return view('portal.dashboard', [
            'user' => $user,
            'groupedApps' => $groupedApps,
            'applications' => $applications,
        ]);
    }
}
