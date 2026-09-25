<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard | KelasKita')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-bg font-sans text-dark antialiased">

    <div class="lg:flex min-h-screen">

        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-dark text-white">
            <div class="flex items-center gap-3 px-6 h-16 border-b border-white/10">
                <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-primary text-white">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M9.664 1.319a.75.75 0 0 1 .672 0 41.059 41.059 0 0 1 8.198 5.424.751.751 0 0 1 0 1.141c-1.362 1.151-2.653 2.162-3.828 3.14-.54 1.89-1.892 3.507-3.641 5.269a.75.75 0 0 1-1.13 0c-1.75-1.762-3.101-3.379-3.641-5.269C4.669 10.045 3.378 9.034 2.016 7.883a.75.75 0 0 1 0-1.141 41.06 41.06 0 0 1 7.648-5.423ZM8.5 6.375a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <a href="{{ url('/dashboard') }}" class="text-lg font-semibold">KelasKita</a>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-1">
                <a href="{{ url('/dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium bg-primary text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                    Dashboard
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:bg-white/10 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                    </svg>
                    Materi
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:bg-white/10 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Quiz
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:bg-white/10 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    Profil
                </a>
            </nav>

            <div class="p-4">
                <a href="{{ url('/') }}" class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-white/80 bg-white/10 hover:bg-white/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                    </svg>
                    Keluar
                </a>
            </div>
        </aside>

        <div class="flex-1 lg:ml-64">

            <header class="lg:hidden sticky top-0 z-20 bg-dark text-white">
                <div class="flex items-center justify-between h-16 px-4">
                    <span class="flex items-center gap-2.5">
                        <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary text-white">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M9.664 1.319a.75.75 0 0 1 .672 0 41.059 41.059 0 0 1 8.198 5.424.751.751 0 0 1 0 1.141c-1.362 1.151-2.653 2.162-3.828 3.14-.54 1.89-1.892 3.507-3.641 5.269a.75.75 0 0 1-1.13 0c-1.75-1.762-3.101-3.379-3.641-5.269C4.669 10.045 3.378 9.034 2.016 7.883a.75.75 0 0 1 0-1.141 41.06 41.06 0 0 1 7.648-5.423ZM8.5 6.375a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <span class="text-base font-semibold">KelasKita</span>
                    </span>
                    <a href="{{ url('/') }}" class="text-sm text-white/70 hover:text-white">Keluar</a>
                </div>
                <nav class="flex gap-1 px-3 pb-3 overflow-x-auto">
                    <a href="{{ url('/dashboard') }}" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-white">Dashboard</a>
                    <a href="#" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium text-white/70">Materi</a>
                    <a href="#" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium text-white/70">Quiz</a>
                    <a href="#" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium text-white/70">Profil</a>
                </nav>
            </header>

            <main class="p-6 lg:p-10">
                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>