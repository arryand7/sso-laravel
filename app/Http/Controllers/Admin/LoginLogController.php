<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoginLogController extends Controller
{
    /**
     * Display a listing of login logs.
     */
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);
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
    public function export(Request $request): StreamedResponse
    {
        $filename = 'login-logs-'.now()->format('Ymd-His').'.csv';
        $query = $this->filteredQuery($request)->orderBy('login_at', 'desc');

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Username', 'Email', 'Application', 'IP Address', 'User Agent', 'Login At']);

            $query->chunk(500, function ($logs) use ($handle): void {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->user?->name,
                        $log->user?->username,
                        $log->user?->email,
                        $log->client_app,
                        $log->ip_address,
                        $log->user_agent,
                        optional($log->login_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function filteredQuery(Request $request): Builder
    {
        $query = LoginLog::with('user');

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($clientApp = $request->input('client_app')) {
            $query->where('client_app', $clientApp);
        }

        if ($startDate = $request->input('start_date')) {
            $query->where('login_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->where('login_at', '<=', $endDate.' 23:59:59');
        }

        return $query;
    }
}
