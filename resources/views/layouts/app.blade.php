<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sabira Connect' }}</title>
    
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#2463eb",
                        "primary-hover": "#1d4ed8",
                        "background-light": "#f6f6f8",
                        "background-dark": "#111621",
                        "surface-light": "#ffffff",
                        "surface-dark": "#1a202c",
                        "border-light": "#f0f1f4",
                        "border-dark": "#2d3748",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                },
            },
        }
    </script>
    <style>
        /* Custom scrollbar for better aesthetics */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #64748b; }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-[#111318] dark:text-white font-display overflow-hidden h-screen flex">
    <!-- Sidebar -->
    <aside class="w-72 bg-surface-light dark:bg-background-dark border-r border-border-light dark:border-border-dark hidden md:flex flex-col justify-between h-full transition-colors duration-300">
        <div class="flex flex-col h-full">
            <!-- Logo Area -->
            <div class="px-6 py-5 border-b border-border-light dark:border-border-dark flex items-center gap-3">
                <div class="bg-primary/10 rounded-lg size-10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-2xl">school</span>
                </div>
                <div class="flex flex-col">
                    <h1 class="text-[#111318] dark:text-white text-base font-bold leading-tight">Sabira Connect</h1>
                    <p class="text-[#616e89] dark:text-slate-400 text-xs font-normal">School Ecosystem</p>
                </div>
            </div>
            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 flex flex-col gap-2 overflow-y-auto">
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-blue-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-white/5' }} transition-colors" href="{{ route('dashboard') }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('dashboard') ? 'filled' : '' }}">dashboard</span>
                    <span class="text-sm font-semibold">Dashboard</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('profile.*') ? 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-blue-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-white/5' }} transition-colors" href="{{ route('profile.show') }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('profile.*') ? 'filled' : '' }}">person</span>
                    <span class="text-sm font-medium">Profile</span>
                </a>
                
                @if(auth()->user()->isAdmin())
                <div class="pt-4 mt-auto">
                    <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Administration</p>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-white/5 transition-colors" href="{{ route('admin.dashboard') }}">
                        <span class="material-symbols-outlined">admin_panel_settings</span>
                        <span class="text-sm font-medium">Admin Panel</span>
                    </a>
                </div>
                @endif
            </nav>
            <!-- User Mini Profile -->
            <div class="p-4 border-t border-border-light dark:border-border-dark">
                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-white/5 cursor-pointer transition-colors relative group" x-data="{ open: false }">
                    <div class="size-9 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold border border-slate-200 dark:border-slate-700">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex flex-col overflow-hidden">
                        <span class="text-sm font-medium truncate text-slate-900 dark:text-slate-200">{{ auth()->user()->name }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ auth()->user()->email }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors" title="Logout">
                            <span class="material-symbols-outlined text-lg">logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-full min-w-0 bg-background-light dark:bg-background-dark">
        <!-- Top Navigation -->
        <header class="h-16 flex items-center justify-between px-4 sm:px-8 border-b border-border-light dark:border-border-dark bg-surface-light/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-20">
            <!-- Mobile Menu Trigger -->
            <button class="md:hidden p-2 -ml-2 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg">
                <span class="material-symbols-outlined">menu</span>
            </button>
            
            <!-- Breadcrumb / Title (Mobile) -->
            <div class="flex md:hidden items-center gap-2">
                <span class="font-bold text-lg">Sabira Connect</span>
            </div>
            
            <!-- Search (Desktop) -->
            <div class="hidden md:flex flex-1 max-w-md mx-4">
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                    <input class="w-full pl-10 pr-4 py-2 bg-slate-100 dark:bg-white/5 border-none rounded-lg text-sm text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-primary/50 outline-none placeholder:text-slate-400" placeholder="Cari aplikasi..." type="text"/>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-3">
                <div class="md:hidden">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2 text-slate-600 hover:text-red-500">
                            <span class="material-symbols-outlined">logout</span>
                        </button>
                    </form>
                </div>
                <button class="relative p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-2 right-2.5 size-2 bg-red-500 rounded-full border-2 border-white dark:border-background-dark"></span>
                </button>
            </div>
        </header>
        
        <!-- Scrollable Main Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-8 md:p-12 scroll-smooth">
            <div class="max-w-6xl mx-auto space-y-10 pb-20">
                @if(session('status'))
                <div class="bg-blue-50 text-blue-700 p-4 rounded-lg mb-6 border border-blue-100 flex items-center gap-3">
                    <span class="material-symbols-outlined">info</span>
                    {{ session('status') }}
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
