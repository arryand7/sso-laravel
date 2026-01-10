@extends('layouts.app')

@section('content')
<!-- Hero / Greeting Section -->
<div class="space-y-2 animate-fade-in-up">
    <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white tracking-tight">
        Selamat datang, {{ auth()->user()->name }}! 👋
    </h1>
    <p class="text-slate-500 dark:text-slate-400 text-lg">
        Berikut adalah aplikasi yang tersedia untuk role <span class="font-medium text-slate-900 dark:text-slate-200">{{ auth()->user()->roles->pluck('name')->join(', ') }}</span>.
    </p>
</div>

@forelse ($groupedApps as $category => $apps)
    @php
        // Determine section color based on category name (simple hash or mapping)
        $colors = ['bg-primary', 'bg-indigo-500', 'bg-emerald-500', 'bg-orange-500', 'bg-purple-500'];
        $sectionColor = $colors[crc32($category) % count($colors)];
    @endphp
    <!-- Section: {{ $category }} -->
    <section class="animate-fade-in-up" style="animation-delay: {{ $loop->iteration * 100 }}ms">
        <div class="flex items-center gap-3 mb-6">
            <div class="h-8 w-1 {{ $sectionColor }} rounded-full"></div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $category }}</h2>
            <span class="px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-semibold">{{ $apps->count() }} Apps</span>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($apps as $app)
                @php
                    // Determine card color/icon logic
                    // This is a simple visual mapping for demo purposes based on design
                    $colorMap = [
                        'akademik' => ['bg' => 'bg-blue-50 dark:bg-blue-900/20', 'text' => 'text-blue-600 dark:text-blue-400', 'icon' => 'school'],
                        'lms' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'text' => 'text-emerald-600 dark:text-emerald-400', 'icon' => 'cast_for_education'],
                        'finance' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/20', 'text' => 'text-cyan-600 dark:text-cyan-400', 'icon' => 'payments'],
                        'perpustakaan' => ['bg' => 'bg-orange-50 dark:bg-orange-900/20', 'text' => 'text-orange-600 dark:text-orange-400', 'icon' => 'local_library'],
                        'internal' => ['bg' => 'bg-purple-50 dark:bg-purple-900/20', 'text' => 'text-purple-600 dark:text-purple-400', 'icon' => 'mail'],
                    ];
                    // Normalize category for key
                    $catKey = Str::slug($app->category ?? 'default');
                    // Fallback
                    $theme = $colorMap[$catKey] ?? $colorMap[array_rand($colorMap)]; 
                    
                    // Use app icon if defined, else generic
                    $icon = $app->icon ?? $theme['icon'];
                @endphp
                
                <div class="group relative bg-surface-light dark:bg-surface-dark border border-border-light dark:border-slate-700/50 rounded-xl p-6 hover:shadow-lg hover:border-primary/30 dark:hover:border-primary/30 transition-all duration-300 flex flex-col h-full">
                    <div class="flex items-start justify-between mb-4">
                        <div class="size-12 rounded-xl {{ $theme['bg'] }} {{ $theme['text'] }} flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl">{{ $icon }}</span>
                        </div>
                        <span class="px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">{{ $app->category }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 group-hover:text-primary transition-colors">{{ $app->name }}</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6 flex-1">
                        {{ $app->description ?? 'Tidak ada deskripsi tersedia.' }}
                    </p>
                    @if(!empty($app->sso_login_url))
                        <a href="{{ $app->sso_login_url }}" class="inline-flex items-center text-sm font-semibold text-primary group-hover:underline decoration-2 underline-offset-4 decoration-primary/30 group-hover:decoration-primary cursor-pointer">
                            Buka Aplikasi <span class="material-symbols-outlined text-base ml-1 transition-transform group-hover:translate-x-1">arrow_forward</span>
                        </a>
                    @else
                        <form action="{{ route('oauth.authorize') }}" method="GET" class="mt-auto">
                            <input type="hidden" name="client_id" value="{{ $app->client_id }}">
                            <input type="hidden" name="redirect_uri" value="{{ $app->redirect_uri }}">
                            <input type="hidden" name="response_type" value="code">
                            <input type="hidden" name="scope" value="openid profile email roles">
                            <button type="submit" class="inline-flex items-center text-sm font-semibold text-primary group-hover:underline decoration-2 underline-offset-4 decoration-primary/30 group-hover:decoration-primary cursor-pointer">
                                Buka Aplikasi <span class="material-symbols-outlined text-base ml-1 transition-transform group-hover:translate-x-1">arrow_forward</span>
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@empty
    <div class="text-center py-20">
        <div class="bg-slate-100 rounded-full size-20 flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-4xl text-slate-400">apps_outage</span>
        </div>
        <h3 class="text-lg font-bold text-slate-900">Belum ada aplikasi</h3>
        <p class="text-slate-500">Anda belum memiliki akses ke aplikasi apapun.</p>
    </div>
@endforelse

<!-- Quick Help Banner -->
<div class="rounded-xl bg-gradient-to-r from-slate-900 to-slate-800 p-8 text-white relative overflow-hidden group mt-12 animate-fade-in-up" style="animation-delay: 500ms">
    <!-- Abstract Background Pattern -->
    <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-16 -mt-16 blur-3xl group-hover:bg-primary/20 transition-colors duration-500"></div>
    <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div>
            <h3 class="text-xl font-bold mb-2">Butuh bantuan akses?</h3>
            <p class="text-slate-300 max-w-lg">Jika Anda mengalami kendala saat mengakses aplikasi atau tidak menemukan aplikasi yang seharusnya ada, silakan hubungi tim IT.</p>
        </div>
        <button class="bg-white text-slate-900 hover:bg-slate-100 font-bold py-2.5 px-5 rounded-lg text-sm transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">support_agent</span>
            Hubungi IT Support
        </button>
    </div>
</div>
@endsection
