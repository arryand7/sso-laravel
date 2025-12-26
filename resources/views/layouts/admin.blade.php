<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} - Sabira Connect</title>
    
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#2463eb",
                        "background-light": "#f6f6f8",
                        "background-dark": "#111621",
                        "slate-850": "#1a202c", 
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        /* Custom scrollbar for cleaner look */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-slate-900 dark:text-white h-screen flex overflow-hidden">
    <!-- Sidebar -->
    <aside class="hidden md:flex flex-col w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 h-full flex-shrink-0">
        <div class="p-6 flex flex-col h-full justify-between">
            <div class="flex flex-col gap-6">
                <!-- Branding -->
                <div class="flex gap-3 items-center">
                    <div class="bg-primary/10 rounded-lg size-10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-2xl">school</span>
                    </div>
                    <div class="flex flex-col">
                        <h1 class="text-slate-900 dark:text-white text-base font-bold leading-normal">Sabira Connect</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-xs font-medium leading-normal">Admin Portal</p>
                    </div>
                </div>
                <!-- Navigation -->
                <nav class="flex flex-col gap-2">
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-primary' : 'hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors' }}" href="{{ route('admin.dashboard') }}">
                        <span class="material-symbols-outlined text-xl" data-weight="fill">dashboard</span>
                        <p class="text-sm font-medium leading-normal">Dashboard</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-primary/10 text-primary' : 'hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors' }}" href="{{ route('admin.users.index') }}">
                        <span class="material-symbols-outlined text-xl">group</span>
                        <p class="text-sm font-medium leading-normal">Users</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.applications.*') ? 'bg-primary/10 text-primary' : 'hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors' }}" href="{{ route('admin.applications.index') }}">
                        <span class="material-symbols-outlined text-xl">apps</span>
                        <p class="text-sm font-medium leading-normal">Applications</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.roles.*') ? 'bg-primary/10 text-primary' : 'hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors' }}" href="{{ route('admin.roles.index') }}">
                        <span class="material-symbols-outlined text-xl">security</span>
                        <p class="text-sm font-medium leading-normal">Roles</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.logins.*') ? 'bg-primary/10 text-primary' : 'hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors' }}" href="{{ route('admin.logins.index') }}">
                        <span class="material-symbols-outlined text-xl">history</span>
                        <p class="text-sm font-medium leading-normal">Login Logs</p>
                    </a>
                    @if(auth()->user()->hasRole('superadmin'))
                        <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('admin.server.*') ? 'bg-primary/10 text-primary' : 'hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors' }}" href="{{ route('admin.server.index') }}">
                            <span class="material-symbols-outlined text-xl">dns</span>
                            <p class="text-sm font-medium leading-normal">Server</p>
                        </a>
                    @endif
                </nav>
            </div>
            <!-- Footer Action -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition-colors mt-auto">
                    <span class="material-symbols-outlined text-xl">logout</span>
                    <p class="text-sm font-medium leading-normal">Logout</p>
                </button>
            </form>
        </div>
    </aside>
    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto relative flex flex-col h-full w-full">
        <div class="w-full max-w-[1400px] mx-auto p-4 md:p-8 flex flex-col gap-8">
            @if(session('status'))
                <div class="bg-blue-50 text-blue-700 p-4 rounded-lg border border-blue-100 flex items-center gap-3">
                    <span class="material-symbols-outlined">info</span>
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 text-red-700 p-4 rounded-lg border border-red-100">
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @yield('content')
        </div>
    </main>
</body>
</html>
