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

    <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet"/>
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" rel="stylesheet"/>
    
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

        .row { display: flex; flex-wrap: wrap; gap: 0.75rem; }
        .col-sm-12 { flex: 0 0 100%; max-width: 100%; }
        .d-flex { display: flex; }
        .align-items-center { align-items: center; }
        .justify-end { justify-content: flex-end; }
        .gap-2 { gap: 0.5rem; }
        .mb-3 { margin-bottom: 0.75rem; }
        .btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; border: 1px solid #e2e8f0; background: #f8fafc; color: #334155; cursor: pointer; }
        .btn-secondary { background: #f1f5f9; }
        .btn-group { display: inline-flex; gap: 0.35rem; flex-wrap: wrap; }
        .dt-buttons { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .dataTables_length { display: flex; align-items: center; }
        .dataTables_filter { display: flex; justify-content: flex-end; }
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label { font-size: 0.8rem; color: #475569; display: flex; align-items: center; gap: 0.5rem; }
        .dataTables_wrapper .dataTables_filter label { white-space: nowrap; }
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select { border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.35rem 0.6rem; font-size: 0.85rem; background: #fff; }
        .dataTables_wrapper .dt-buttons .btn { border: 1px solid #e2e8f0; }
        .dataTables_wrapper .dataTables_info { font-size: 0.8rem; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 0.5rem; padding: 0.2rem 0.6rem; margin-left: 0.25rem; border: 1px solid transparent; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #2563eb; color: #fff !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #e2e8f0; color: #0f172a !important; border: 1px solid #e2e8f0; }
        .dataTables_wrapper table.dataTable.no-footer { border-bottom: 1px solid #e2e8f0; }

        .dark .btn { border-color: #334155; background: #0f172a; color: #e2e8f0; }
        .dark .btn-secondary { background: #111827; }
        .dark .dataTables_wrapper .dataTables_length label,
        .dark .dataTables_wrapper .dataTables_filter label { color: #cbd5f5; }
        .dark .dataTables_wrapper .dataTables_filter input,
        .dark .dataTables_wrapper .dataTables_length select { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        .dark .dataTables_wrapper .dataTables_info { color: #94a3b8; }
        .dark .dataTables_wrapper table.dataTable.no-footer { border-bottom: 1px solid #334155; }

        @media (min-width: 768px) {
            .col-md-6 { flex: 0 0 50%; max-width: 50%; }
            .col-md-5 { flex: 0 0 41.666666%; max-width: 41.666666%; }
            .col-md-7 { flex: 0 0 58.333333%; max-width: 58.333333%; }
        }
        @media (min-width: 1024px) {
            .dataTables_wrapper .row.mb-3 { flex-wrap: nowrap; }
            .dataTables_wrapper .row.mb-3 > .col-md-7 { flex: 1 1 auto; min-width: 0; }
            .dataTables_wrapper .row.mb-3 > .col-md-5 { flex: 0 0 auto; margin-left: auto; }
        }
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

            <footer class="pt-6 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
                Copyright {{ date('Y') }} Sabira Connect. Built by Ryand Arifriantoni (arryand7@gmail.com).
            </footer>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.js-admin-table[data-datatable="true"]').forEach((table) => {
                const $table = $(table);

                $table.DataTable({
                    paging: true,
                    ordering: true,
                    info: true,
                    lengthChange: true,
                    pageLength: 10,
                    dom: "<'row mb-3 align-items-center'<'col-sm-12 col-md-7 d-flex align-items-center gap-2'B l><'col-sm-12 col-md-5 d-flex align-items-center justify-end'f>>t<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    buttons: [
                        { extend: 'copy', className: 'btn btn-secondary' },
                        { extend: 'csv', className: 'btn btn-secondary' },
                        { extend: 'excel', className: 'btn btn-secondary' },
                        { extend: 'pdf', className: 'btn btn-secondary' },
                        { extend: 'print', className: 'btn btn-secondary' },
                        { extend: 'colvis', className: 'btn btn-secondary' },
                    ],
                    language: {
                        search: 'Cari:',
                        lengthMenu: 'Tampilkan _MENU_ entri',
                        info: 'Menampilkan _START_ hingga _END_ dari _TOTAL_ entri',
                        infoEmpty: 'Tidak ada data',
                        zeroRecords: 'Tidak ada data yang cocok',
                        paginate: {
                            previous: 'Sebelumnya',
                            next: 'Berikutnya',
                        },
                    },
                });
            });
        });
    </script>
</body>
</html>
