<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Admin | KelasKita')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-bg font-sans text-dark antialiased">

    <div class="lg:flex min-h-screen">

        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white text-dark border-r border-dark/10">
            <div class="flex items-center gap-3 px-6 h-16 border-b border-dark/10">
                <a href="{{ url('/admin/dashboard') }}" class="flex items-center gap-3">
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="Logo KelasKita"
                        class="w-9 h-9 object-contain"
                    />
                    <span class="text-lg font-semibold text-primary">KelasKita</span>
                </a>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-1">
                <a href="{{ url('/admin/dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium bg-primary text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                    Dashboard
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-primary/80 hover:bg-lavender hover:text-primary-dark">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                    </svg>
                    Pengguna
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-primary/80 hover:bg-lavender hover:text-primary-dark">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
                    </svg>
                    Pelajaran
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-primary/80 hover:bg-lavender hover:text-primary-dark">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                    </svg>
                    Materi
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-primary/80 hover:bg-lavender hover:text-primary-dark">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Quiz
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-primary/80 hover:bg-lavender hover:text-primary-dark">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.149-.894Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    Pengaturan
                </a>
            </nav>

            <div class="p-4">
                <a href="{{ url('/') }}" class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-primary bg-lavender hover:bg-primary hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                    </svg>
                    Keluar
                </a>
            </div>
        </aside>

        <div class="flex-1 lg:ml-64">

            <header class="lg:hidden sticky top-0 z-20 bg-white text-dark border-b border-dark/10">
                <div class="flex items-center justify-between h-16 px-4">
                    <span class="flex items-center gap-2.5">
                        <img
                            src="{{ asset('images/logo.png') }}"
                            alt="Logo KelasKita"
                            class="w-8 h-8 object-contain"
                        />
                        <span class="text-base font-semibold text-primary">KelasKita</span>
                    </span>
                    <a href="{{ url('/') }}" class="text-sm text-primary hover:text-primary-dark">Keluar</a>
                </div>
                <nav class="flex gap-1 px-3 pb-3 overflow-x-auto">
                    <a href="{{ url('/admin/dashboard') }}" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-white">Dashboard</a>
                    <a href="#" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium text-primary/80">Pengguna</a>
                    <a href="#" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium text-primary/80">Pelajaran</a>
                    <a href="#" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium text-primary/80">Materi</a>
                    <a href="#" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium text-primary/80">Quiz</a>
                    <a href="#" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium text-primary/80">Pengaturan</a>
                </nav>
            </header>

            <main class="p-6 lg:p-10">
                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>