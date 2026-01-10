<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>{{ config('app.name', 'Sabira Connect') }} - Welcome</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#2463eb",
                        "primary-foreground": "#ffffff",
                        "background-light": "#f8fafc", /* slate-50 */
                        "background-dark": "#0f172a", /* slate-900 */
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"],
                        "sans": ["Inter", "sans-serif"],
                    },
                },
            },
        }
    </script>
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .bg-gradient-blur {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: -1;
        }
        .blob {
            position: absolute; filter: blur(80px); opacity: 0.6; animation: float 10s infinite ease-in-out;
        }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: #dbeafe; border-radius: 50%; }
        .blob-2 { top: 20%; right: -10%; width: 400px; height: 400px; background: #e0e7ff; border-radius: 40%; animation-delay: 2s; }
        .blob-3 { bottom: -10%; left: 20%; width: 600px; height: 600px; background: #f1f5f9; border-radius: 45%; animation-delay: 4s; }
        @keyframes float { 0% { transform: translate(0, 0); } 50% { transform: translate(20px, 40px); } 100% { transform: translate(0, 0); } }
    </style>
</head>
<body class="font-display bg-background-light text-slate-900 min-h-screen flex flex-col relative overflow-x-hidden">
    <!-- Decorative Background -->
    <div class="bg-gradient-blur">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-50 px-4 py-4 sm:px-8">
        <div class="glass mx-auto max-w-7xl rounded-2xl px-6 py-3 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center size-10 rounded-lg bg-primary text-white shadow-md">
                    <span class="material-symbols-outlined" style="font-size: 24px;">school</span>
                </div>
                <h2 class="text-slate-900 text-lg font-bold tracking-tight">Sabira Connect</h2>
            </div>
            <!-- Desktop Nav -->
            <nav class="hidden md:flex items-center gap-8">
                <a class="text-slate-600 hover:text-primary text-sm font-medium transition-colors" href="#">Beranda</a>
                <a class="text-slate-600 hover:text-primary text-sm font-medium transition-colors" href="#">Fitur</a>
                <a class="text-slate-600 hover:text-primary text-sm font-medium transition-colors" href="#">Hubungi Admin</a>
            </nav>
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="hidden md:flex h-9 items-center justify-center rounded px-4 bg-primary text-white text-sm font-medium shadow hover:bg-primary/90 transition-all">
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden md:flex h-9 items-center justify-center rounded px-4 bg-primary text-white text-sm font-medium shadow hover:bg-primary/90 transition-all">
                        <span>Masuk / Login</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>
    <!-- Main Content -->
    <main class="flex-grow flex flex-col pt-32 pb-20 px-4 sm:px-8 max-w-7xl mx-auto w-full">
        <!-- Hero Section -->
        <div class="flex flex-col lg:flex-row items-center justify-between gap-12 lg:gap-20">
            <!-- Hero Text Content -->
            <div class="flex flex-col items-center lg:items-start text-center lg:text-left flex-1 max-w-2xl mx-auto lg:mx-0">
                <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm text-blue-700 mb-6 shadow-sm hover:bg-blue-100 transition-colors cursor-default">
                    <span class="material-symbols-outlined text-[18px]">verified_user</span>
                    <span class="font-medium">SSO Pusat Ekosistem Sabira</span>
                </div>
                <h1 class="text-slate-900 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.15] mb-6">
                    Satu Akun untuk <br class="hidden sm:block"/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-blue-400">Seluruh Ekosistem</span> Sekolah
                </h1>
                <p class="text-slate-600 text-lg sm:text-xl font-normal leading-relaxed mb-8 max-w-lg">
                    Kelola identitas, akses aplikasi, dan integrasikan data sekolah Anda dengan aman dan terpusat melalui Sabira Connect.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto h-11 px-8 rounded bg-primary text-white text-base font-semibold shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">dashboard</span>
                            Ke Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="w-full sm:w-auto h-11 px-8 rounded bg-primary text-white text-base font-semibold shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">login</span>
                            Masuk / Login
                        </a>
                    @endauth
                    <button class="w-full sm:w-auto h-11 px-8 rounded border border-slate-200 bg-white text-slate-700 text-base font-medium hover:bg-slate-50 transition-all flex items-center justify-center">
                        Dokumentasi
                    </button>
                </div>
                <div class="mt-10 flex items-center gap-4 text-slate-500 text-sm">
                    <div class="flex -space-x-2">
                        <div class="size-8 rounded-full border-2 border-white bg-slate-200"></div>
                        <div class="size-8 rounded-full border-2 border-white bg-slate-300"></div>
                        <div class="size-8 rounded-full border-2 border-white bg-slate-400"></div>
                    </div>
                    <p>Digunakan oleh <span class="font-semibold text-slate-700">1,200+</span> Guru &amp; Staf</p>
                </div>
            </div>
            <!-- Hero Visual -->
            <div class="flex-1 w-full max-w-md lg:max-w-full flex justify-center lg:justify-end">
                <div class="relative w-full max-w-[480px]">
                    <div class="absolute -top-6 -right-6 size-24 rounded-full bg-blue-500/10 blur-xl animate-pulse"></div>
                    <div class="absolute -bottom-8 -left-8 size-32 rounded-full bg-indigo-500/10 blur-xl"></div>
                    <div class="glass rounded-xl shadow-xl overflow-hidden relative z-10">
                        <div class="bg-gradient-to-r from-slate-50 to-white border-b border-slate-100 p-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex space-x-1.5"><div class="size-3 rounded-full bg-red-400"></div><div class="size-3 rounded-full bg-amber-400"></div><div class="size-3 rounded-full bg-emerald-400"></div></div>
                                <span class="ml-2 text-xs font-mono text-slate-400">identity.sabira.id</span>
                            </div>
                            <div class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-100 text-emerald-600 text-[10px] font-bold uppercase tracking-wider">System Active</div>
                        </div>
                        <div class="p-6 flex flex-col gap-6">
                            <div class="flex items-center gap-4 group">
                                <div class="size-12 rounded-lg bg-blue-50 flex items-center justify-center text-primary"><span class="material-symbols-outlined">dns</span></div>
                                <div class="flex flex-col"><span class="text-xs text-slate-500 font-medium uppercase tracking-wide">Status Server</span><div class="flex items-center gap-1.5"><span class="relative flex h-2.5 w-2.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span></span><span class="text-slate-800 font-bold text-lg">Online (99.9%)</span></div></div>
                            </div>
                            <div class="h-px w-full bg-slate-100"></div>
                            <div class="flex items-center gap-4 group">
                                <div class="size-12 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600"><span class="material-symbols-outlined">lock</span></div>
                                <div class="flex flex-col"><span class="text-xs text-slate-500 font-medium uppercase tracking-wide">Protokol Keamanan</span><span class="text-slate-800 font-bold text-lg">OpenID / SAML 2.0</span></div>
                            </div>
                            <div class="h-px w-full bg-slate-100"></div>
                            <div class="flex items-center gap-4 group">
                                <div class="size-12 rounded-lg bg-violet-50 flex items-center justify-center text-violet-600"><span class="material-symbols-outlined">apps</span></div>
                                <div class="flex flex-col"><span class="text-xs text-slate-500 font-medium uppercase tracking-wide">Aplikasi Terhubung</span><span class="text-slate-800 font-bold text-lg">12 Layanan Sekolah</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Features Grid -->
        <div class="mt-24 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 rounded-xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="size-10 rounded bg-blue-50 text-blue-600 flex items-center justify-center mb-4"><span class="material-symbols-outlined">key</span></div>
                <h3 class="text-slate-900 font-bold text-lg mb-2">Single Sign-On</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Cukup ingat satu password untuk mengakses seluruh layanan.</p>
            </div>
            <div class="p-6 rounded-xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="size-10 rounded bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4"><span class="material-symbols-outlined">shield_lock</span></div>
                <h3 class="text-slate-900 font-bold text-lg mb-2">Keamanan Terpusat</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Melindungi data sensitif siswa dan guru dengan enkripsi standar.</p>
            </div>
            <div class="p-6 rounded-xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="size-10 rounded bg-purple-50 text-purple-600 flex items-center justify-center mb-4"><span class="material-symbols-outlined">sync_alt</span></div>
                <h3 class="text-slate-900 font-bold text-lg mb-2">Integrasi Seamless</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Sinkronisasi data otomatis antar aplikasi dalam ekosistem.</p>
            </div>
        </div>
    </main>
    <footer class="border-t border-slate-200 bg-white/50 backdrop-blur-sm mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 py-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <div class="size-6 flex items-center justify-center rounded bg-slate-200 text-slate-600"><span class="material-symbols-outlined text-[16px]">school</span></div>
                <span class="text-sm font-semibold text-slate-700">Sabira Connect</span>
            </div>
            <div class="text-sm text-slate-500">Copyright {{ date('Y') }} Sabira Connect. Built by Ryand Arifriantoni (arryand7@gmail.com).</div>
            <div class="flex gap-6"><a class="text-xs text-slate-500 hover:text-slate-900 font-medium" href="#">Privacy Policy</a><a class="text-xs text-slate-500 hover:text-slate-900 font-medium" href="#">Terms</a></div>
        </div>
    </footer>
</body>
</html>
