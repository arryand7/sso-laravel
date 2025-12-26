<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    /**
     * Display a listing of login logs.
     */
    public function index(Request $request)
    {
        $query = LoginLog::with('user');

        // Filter by user search
        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter by client app
        if ($clientApp = $request->input('client_app')) {
            $query->where('client_app', $clientApp);
        }

        // Filter by date range
        if ($startDate = $request->input('start_date')) {
            $query->where('login_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->where('login_at', '<=', $endDate . ' 23:59:59');
        }

        $logs = $query->orderBy('login_at', 'desc')->paginate(25)->withQueryString();
        
        // Get distinct client apps for filter
        $clientApps = LoginLog::distinct()->pluck('client_app');

        return view('admin.logins.index', [
            'logs' => $logs,
            'clientApps' => $clientApps,
        ]);
    }

    /**
     * Export login logs.
     */
    public function export(Request $request)
    {
        // Export logic can be implemented later
        return back()->with('status', 'Export fitur akan diimplementasi.');
    }
}
