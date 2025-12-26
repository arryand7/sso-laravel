<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Application;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistics
        $totalUsers = User::count();
        $totalApps = Application::where('is_active', true)->count();
        
        $startOfWeek = Carbon::now()->startOfWeek();
        $loginsThisWeek = LoginLog::where('login_at', '>=', $startOfWeek)->count();
        
        // Recent Activity (Mixed)
        // For now just getting recent logins as activity
        $recentActivities = LoginLog::with(['user', 'user.roles'])
                            ->latest('login_at')
                            ->take(5)
                            ->get()
                            ->map(function($log) {
                                return [
                                    'type' => 'login',
                                    'title' => 'User Login',
                                    'description' => $log->user->name . ' logged in to ' . ($log->client_app ?? 'Portal'),
                                    'time' => $log->login_at->diffForHumans(),
                                    'icon' => 'login',
                                    'color' => 'blue'
                                ];
                            });

        return view('admin.dashboard', compact('totalUsers', 'totalApps', 'loginsThisWeek', 'recentActivities'));
    }
}
