@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
<!-- Main Card -->
<div class="bg-white dark:bg-[#1A2230] rounded-xl shadow-[0_8px_30px_rgba(0,0,0,0.04)] dark:shadow-none border border-transparent dark:border-[#2A3441] overflow-hidden">
    <!-- Card Header & Branding -->
    <div class="flex flex-col items-center pt-10 pb-2 px-8 text-center">
        <!-- Logo Container -->
        <div class="size-14 bg-primary/10 dark:bg-primary/20 rounded-xl flex items-center justify-center text-primary mb-6">
            <span class="material-symbols-outlined text-3xl">school</span>
        </div>
        <h1 class="text-2xl font-bold leading-tight tracking-[-0.015em] text-[#111318] dark:text-white mb-2">
            Masuk ke Sabira Connect
        </h1>
        <p class="text-[#616e89] dark:text-[#94a3b8] text-base font-normal leading-normal">
            Portal ekosistem sekolah Sabira
        </p>
    </div>

    @if(($oauth['google'] ?? false) || ($oauth['facebook'] ?? false))
        <div class="px-8 pb-6">
            <div class="grid gap-3">
                @if($oauth['google'] ?? false)
                    <a href="{{ url('/auth/google') }}{{ !empty($continue) ? '?continue=' . urlencode($continue) : '' }}" class="flex items-center justify-center gap-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#111621] px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                        <span class="flex size-9 items-center justify-center rounded-full bg-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="size-5">
                                <path fill="#EA4335" d="M24 9.5c3.28 0 5.53 1.42 6.8 2.6l4.65-4.65C32.6 4.6 28.7 2.5 24 2.5 14.9 2.5 7.2 8.4 4.6 16.5l5.6 4.35C11.4 14.1 17.1 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.5 24.5c0-1.6-.15-2.8-.4-4H24v7.6h12.7c-.26 2-1.65 5.1-4.7 7.1l5.7 4.4c3.4-3.1 5.8-7.7 5.8-15.1z"/>
                                <path fill="#FBBC05" d="M10.2 28.1c-.5-1.5-.8-3.1-.8-4.8 0-1.7.3-3.3.8-4.8l-5.6-4.35C3 17.1 2.5 20.7 2.5 24.5c0 3.8.5 7.4 2.1 10.4l5.6-4.35z"/>
                                <path fill="#34A853" d="M24 46.5c6.5 0 11.9-2.1 15.9-5.8l-5.7-4.4c-1.6 1.1-3.7 2-7.2 2-6.9 0-12.6-4.6-14.7-11l-5.6 4.35C7.2 39.6 14.9 46.5 24 46.5z"/>
                            </svg>
                        </span>
                        Masuk dengan Google
                    </a>
                    @if(!empty($oauth['google_domains'] ?? ''))
                        <p class="text-xs text-slate-400 text-center">
                            Hanya akun dengan domain: {{ $oauth['google_domains'] }}
                        </p>
                    @endif
                @endif

                @if($oauth['facebook'] ?? false)
                    <a href="{{ url('/auth/facebook') }}{{ !empty($continue) ? '?continue=' . urlencode($continue) : '' }}" class="flex items-center justify-center gap-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#111621] px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                        <span class="flex size-9 items-center justify-center rounded-full bg-[#1877F2] text-white shadow-sm font-bold">f</span>
                        Masuk dengan Facebook
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-3 text-xs text-slate-400 mt-5">
                <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
                atau masuk dengan akun
                <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
            </div>
        </div>
    @endif
    
    <!-- Login Form -->
    <div class="p-8">
        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
            @csrf
            
            @if ($errors->any())
                <div class="bg-red-50 text-red-600 text-sm p-3 rounded-lg">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Username Input -->
            <div class="flex flex-col gap-2">
                <label class="text-[#111318] dark:text-[#e2e8f0] text-sm font-semibold leading-normal" for="username">
                    Nama Pengguna
                </label>
                <input class="form-input flex w-full rounded-lg border border-[#dbdee6] dark:border-[#374151] bg-white dark:bg-[#111621] px-4 h-12 text-[#111318] dark:text-white placeholder:text-[#9ca3af] focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all duration-200 text-base" id="username" name="username" value="{{ old('username') }}" minlength="3" placeholder="username/email" required autofocus type="text"/>
            </div>
            
            <!-- Password Input -->
            <div class="flex flex-col gap-2">
                <div class="flex justify-between items-center">
                    <label class="text-[#111318] dark:text-[#e2e8f0] text-sm font-semibold leading-normal" for="password">
                        Kata Sandi
                    </label>
                </div>
                <div class="relative flex w-full items-center" x-data="{ show: false }">
                    <input class="form-input flex w-full rounded-lg border border-[#dbdee6] dark:border-[#374151] bg-white dark:bg-[#111621] pl-4 pr-12 h-12 text-[#111318] dark:text-white placeholder:text-[#9ca3af] focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all duration-200 text-base" id="password" name="password" required :type="show ? 'text' : 'password'" placeholder="••••••••"/>
                    <button class="absolute right-0 top-0 h-full px-4 text-[#616e89] hover:text-[#111318] dark:text-[#9ca3af] dark:hover:text-white transition-colors flex items-center justify-center outline-none focus:text-primary" type="button" @click="show = !show">
                        <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                    </button>
                </div>
            </div>
            
            <!-- Actions Row -->
            <div class="flex items-center justify-between mt-1">
                <label class="flex items-center gap-2.5 cursor-pointer group select-none">
                    <div class="relative flex items-center">
                        <input class="peer h-4 w-4 cursor-pointer appearance-none rounded border border-[#dbdee6] dark:border-[#475569] bg-white dark:bg-[#1e293b] checked:border-primary checked:bg-primary transition-all focus:ring-2 focus:ring-primary/20 focus:ring-offset-0 focus:outline-none" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}/>
                        <span class="material-symbols-outlined pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-white opacity-0 peer-checked:opacity-100 transition-opacity text-[12px]">check</span>
                    </div>
                    <span class="text-[#616e89] dark:text-[#94a3b8] text-sm font-medium group-hover:text-[#111318] dark:group-hover:text-white transition-colors">Ingat saya</span>
                </label>
                @if (Route::has('password.request'))
                    <a class="text-sm font-semibold text-primary hover:text-blue-600 dark:hover:text-blue-400 transition-colors focus:outline-none focus:underline" href="{{ route('password.request') }}">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>
            
            <!-- Submit Button -->
            <button class="mt-2 flex w-full cursor-pointer items-center justify-center rounded-lg h-12 px-6 bg-primary hover:bg-blue-600 active:bg-blue-700 text-white text-base font-bold tracking-[0.015em] transition-all shadow-md shadow-blue-500/10 focus:ring-4 focus:ring-primary/20 focus:outline-none" type="submit">
                Masuk ke Sabira
            </button>
        </form>
    </div>
    
    <!-- Card Footer / Help -->
    <div class="bg-[#fafafa] dark:bg-[#151b26] border-t border-[#f0f1f4] dark:border-[#2A3441] p-4 flex justify-center">
        <a class="group flex items-center gap-2 text-sm font-semibold text-[#616e89] dark:text-[#94a3b8] hover:text-[#111318] dark:hover:text-white transition-colors" href="#">
            <span class="material-symbols-outlined text-[18px] group-hover:text-primary transition-colors">help</span>
            <span>Butuh bantuan?</span>
        </a>
    </div>
</div>
<!-- Alpine JS for password toggle (Using CDN for simplicity as Tailwind is also CDN) -->
<script src="//unpkg.com/alpinejs" defer></script>
@endsection
