@extends('layouts.admin')

@section('content')
<!-- Header Section -->
<div class="flex flex-col gap-4">
    <!-- Breadcrumbs -->
    <div class="flex flex-wrap gap-2 items-center text-sm">
        <a class="text-slate-500 dark:text-slate-400 hover:text-primary transition-colors font-medium" href="#">Home</a>
        <span class="text-slate-400 dark:text-slate-600 font-medium">/</span>
        <a class="text-slate-500 dark:text-slate-400 hover:text-primary transition-colors font-medium" href="#">Admin</a>
        <span class="text-slate-400 dark:text-slate-600 font-medium">/</span>
        <span class="text-slate-900 dark:text-white font-semibold">Dashboard</span>
    </div>
    <!-- Page Heading & Controls -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h2 class="text-slate-900 dark:text-white tracking-tight text-3xl font-bold leading-tight">Statistics Dashboard</h2>
        <div class="flex items-center gap-3">
            <div class="relative">
                <button class="flex items-center gap-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-[20px] text-slate-500 dark:text-slate-400">calendar_today</span>
                    <span>This Week</span>
                </button>
            </div>
            <button class="flex items-center justify-center p-2 rounded-lg bg-primary text-white shadow hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined text-[20px]">refresh</span>
            </button>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
    <!-- Total Users -->
    <div class="flex flex-col gap-1 rounded-xl p-5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Total Users</p>
            <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 text-xl">group</span>
        </div>
        <div class="flex items-baseline gap-2 mt-2">
            <p class="text-slate-900 dark:text-white text-2xl font-bold leading-tight">{{ number_format($totalUsers) }}</p>
        </div>
    </div>
    
    <!-- Active Apps -->
    <div class="flex flex-col gap-1 rounded-xl p-5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Active Apps</p>
            <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 text-xl">apps</span>
        </div>
        <div class="flex items-baseline gap-2 mt-2">
            <p class="text-slate-900 dark:text-white text-2xl font-bold leading-tight">{{ number_format($totalApps) }}</p>
            <span class="inline-flex items-center text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-full">Active</span>
        </div>
    </div>
    
    <!-- Logins This Week -->
    <div class="flex flex-col gap-1 rounded-xl p-5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Logins This Week</p>
            <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 text-xl">login</span>
        </div>
        <div class="flex items-baseline gap-2 mt-2">
            <p class="text-slate-900 dark:text-white text-2xl font-bold leading-tight">{{ number_format($loginsThisWeek) }}</p>
        </div>
    </div>
    
    <!-- Success Rate (Static for now) -->
    <div class="flex flex-col gap-1 rounded-xl p-5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">System Status</p>
            <span class="material-symbols-outlined text-emerald-500 text-xl">check_circle</span>
        </div>
        <div class="flex items-baseline gap-2 mt-2">
            <p class="text-emerald-600 dark:text-emerald-400 text-2xl font-bold leading-tight">Healthy</p>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Activity Timeline -->
    <div class="lg:col-span-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
        <h3 class="text-slate-900 dark:text-white text-lg font-bold leading-tight mb-6">Recent Activity</h3>
        <div class="flex flex-col relative before:absolute before:left-[19px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100 dark:before:bg-slate-800">
            @forelse($recentActivities as $activity)
            <div class="relative flex gap-4 pb-6 last:pb-0">
                <div class="relative z-10 flex-none size-10 rounded-full bg-{{ $activity['color'] }}-50 dark:bg-{{ $activity['color'] }}-900/20 text-{{ $activity['color'] }}-600 dark:text-{{ $activity['color'] }}-400 flex items-center justify-center border border-white dark:border-slate-900">
                    <span class="material-symbols-outlined text-xl">{{ $activity['icon'] }}</span>
                </div>
                <div class="flex flex-col gap-1 pt-1">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $activity['title'] }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $activity['description'] }}</p>
                    <span class="text-xs text-slate-400 mt-1">{{ $activity['time'] }}</span>
                </div>
            </div>
            @empty
            <p class="text-slate-500 text-sm italic">No recent activity.</p>
            @endforelse
        </div>
    </div>

    <!-- Quick Actions / Status -->
    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
        <h3 class="text-slate-900 dark:text-white text-lg font-bold leading-tight mb-6">Quick Actions</h3>
        <div class="flex flex-col gap-3">
            <a href="{{ route('admin.users.create') }}" class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 transition-colors">
                <div class="size-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined">person_add</span>
                </div>
                <span class="font-medium text-slate-700">Add New User</span>
            </a>
            <a href="{{ route('admin.applications.create') }}" class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 transition-colors">
                <div class="size-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined">add_to_queue</span>
                </div>
                <span class="font-medium text-slate-700">Add Application</span>
            </a>
            <a href="{{ route('admin.users.import') }}" class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 transition-colors">
                <div class="size-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined">upload_file</span>
                </div>
                <span class="font-medium text-slate-700">Import Users</span>
            </a>
        </div>
    </div>
</div>
@endsection
