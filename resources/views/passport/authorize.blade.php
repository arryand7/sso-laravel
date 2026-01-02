@extends('layouts.guest')

@section('content')
<div class="bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm p-6 space-y-5">
    <div class="flex items-center gap-3">
        <div class="size-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">verified_user</span>
        </div>
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white">Konfirmasi Akses</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">SSO Sabira Connect</p>
        </div>
    </div>

    <div class="space-y-2">
        <p class="text-sm text-slate-700 dark:text-slate-300">
            Aplikasi <span class="font-semibold text-slate-900 dark:text-white">{{ $client->name }}</span>
            meminta akses ke akun Anda.
        </p>
        <div class="rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-2">Scope yang diminta</p>
            @if(count($scopes))
                <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
                    @foreach($scopes as $scope)
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-base text-primary">check_circle</span>
                            <span>{{ $scope->id }}{{ $scope->description ? ' - '.$scope->description : '' }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-slate-600 dark:text-slate-400">Tidak ada scope tambahan yang diminta.</p>
            @endif
        </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
        <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-1">
            @csrf
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-primary text-white font-semibold hover:bg-blue-700 transition-colors">
                Izinkan
            </button>
        </form>

        <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
            @csrf
            @method('DELETE')
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                Tolak
            </button>
        </form>
    </div>
</div>
@endsection
