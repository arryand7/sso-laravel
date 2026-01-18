@props([
    'ordering' => true,
    'dtPaging' => true,
    'dtInfo' => true,
    'dtSearch' => true,
    'dtLengthChange' => true,
    'dtDom' => null,
])

@php($serverPagination = isset($footer))

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="js-admin-table w-full text-sm text-slate-700 dark:text-slate-200 [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:font-semibold [&_td]:px-4 [&_td]:py-3"
               data-datatable="true"
               data-ordering="{{ $ordering ? 'true' : 'false' }}"
               data-dt-paging="{{ $dtPaging ? 'true' : 'false' }}"
               data-dt-info="{{ $dtInfo ? 'true' : 'false' }}"
               data-dt-search="{{ $dtSearch ? 'true' : 'false' }}"
               data-dt-length-change="{{ $dtLengthChange ? 'true' : 'false' }}"
               data-dt-dom="{{ $dtDom ?? '' }}"
               data-server-pagination="{{ $serverPagination ? 'true' : 'false' }}">
            <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-300">
                {{ $head }}
            </thead>
            <tbody class="text-slate-600 dark:text-slate-300 divide-y divide-slate-100 dark:divide-slate-800 [&_tr]:transition-colors [&_tr:hover]:bg-slate-50 dark:[&_tr:hover]:bg-slate-800/60 [&_tr:nth-child(even)]:bg-slate-50/40 dark:[&_tr:nth-child(even)]:bg-slate-900/40">
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
