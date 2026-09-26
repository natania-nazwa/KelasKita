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

        {{-- Sidebar (desktop) --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-dark text-white">
            <div class="flex items-center gap-3 px-6 h-16 border-b border-white/10">
                <a href="{{ route('user.dashboard') }}" class="flex items-center gap-3">
                    <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-primary text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M9.664 1.319a.75.75 0 0 1 .672 0 41.059 41.059 0 0 1 8.198 5.424.751.751 0 0 1 0 1.141c-1.362 1.151-2.653 2.162-3.828 3.14-.54 1.89-1.892 3.507-3.641 5.269a.75.75 0 0 1-1.13 0c-1.75-1.762-3.101-3.379-3.641-5.269C4.669 10.045 3.378 9.034 2.016 7.883a.75.75 0 0 1 0-1.141 41.06 41.06 0 0 1 7.648-5.423ZM8.5 6.375a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                        </svg>
                    </span>

                    <span class="text-lg font-semibold">KelasKita</span>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1">
                @php
                    $menu = [
                        ['route' => 'user.dashboard', 'label' => 'Dashboard', 'ikon' => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
                        ['route' => 'user.materi', 'label' => 'Materi', 'ikon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25'],
                        ['route' => 'user.quiz', 'label' => 'Quiz', 'ikon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                        ['route' => 'user.profil', 'label' => 'Profil', 'ikon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
                    ];
                @endphp

                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs($item['route']) ? 'bg-primary text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['ikon'] }}" />
                        </svg>

                        {{ $item['label'] }}
                    </a>
                @endforeach

                {{--
                    Menu admin hanya dirender kalau peran di database = admin.
                    Ini PUNYA TAMPILAN saja. Proteksi sesungguhnya ada di
                    middleware "admin" (App\Http\Middleware\EnsureAdmin).
                --}}
                @if (auth()->user()?->isAdmin())
                    <div class="pt-5 mt-5 border-t border-white/10">
                        <p class="px-4 pb-2 font-mono text-[10px] uppercase tracking-[0.14em] text-white/35">
                            Admin
                        </p>

                        <a href="{{ route('admin.dashboard') }}"
                            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.*') ? 'bg-primary text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.03 7.03 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>

                            Dashboard Admin
                        </a>
                    </div>
                @endif
            </nav>

            <div class="p-4">
                <p class="truncate px-1 pb-3 text-xs text-white/40">
                    {{ auth()->user()?->nama }}
                </p>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-sm font-semibold text-white/80 transition hover:bg-white/20 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>

                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 lg:ml-64">

            {{-- Header (mobile) --}}
            <header class="lg:hidden sticky top-0 z-20 bg-dark text-white">
                <div class="flex items-center justify-between h-16 px-4">
                    <a href="{{ route('user.dashboard') }}" class="flex items-center gap-2.5">
                        <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary text-white">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M9.664 1.319a.75.75 0 0 1 .672 0 41.059 41.059 0 0 1 8.198 5.424.751.751 0 0 1 0 1.141c-1.362 1.151-2.653 2.162-3.828 3.14-.54 1.89-1.892 3.507-3.641 5.269a.75.75 0 0 1-1.13 0c-1.75-1.762-3.101-3.379-3.641-5.269C4.669 10.045 3.378 9.034 2.016 7.883a.75.75 0 0 1 0-1.141 41.06 41.06 0 0 1 7.648-5.423ZM8.5 6.375a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                            </svg>
                        </span>

                        <span class="text-base font-semibold">KelasKita</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="text-sm text-white/70 hover:text-white">
                            Keluar
                        </button>
                    </form>
                </div>

                <nav class="flex gap-1 px-3 pb-3 overflow-x-auto">
                    @foreach ($menu as $item)
                        <a href="{{ route($item['route']) }}"
                            class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs($item['route']) ? 'bg-primary text-white' : 'text-white/70' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    @if (auth()->user()?->isAdmin())
                        <a href="{{ route('admin.dashboard') }}"
                            class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('admin.*') ? 'bg-primary text-white' : 'text-white/70' }}">
                            Admin
                        </a>
                    @endif
                </nav>
            </header>

            <main class="p-6 lg:p-10">
                @yield('content')
            </main>
        </div>
    </div>

</body>

</html>
