@props([])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ $head }}
            </thead>
            <tbody class="text-slate-600 dark:text-slate-300 divide-y divide-slate-100 dark:divide-slate-800">
                {{ $body }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="mt-3 border-t border-slate-100 dark:border-slate-800 pt-3 text-slate-500 dark:text-slate-400">
            {{ $footer }}
        </div>
    @endisset
</div>
