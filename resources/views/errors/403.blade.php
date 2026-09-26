<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 | KelasKita</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-brand-bg font-sans text-dark antialiased">
    <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-12 text-center">

        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-24 -left-20 w-96 h-96 rounded-full bg-lavender/60"></div>
            <div class="absolute -bottom-28 -right-16 w-96 h-96 rounded-full bg-[#f0ebff]"></div>
        </div>

        <p class="font-mono text-xs font-semibold uppercase tracking-[0.14em] text-primary">Error 403</p>

        <h1 class="mt-3 text-3xl font-extrabold text-dark sm:text-4xl">Akses Ditolak</h1>

        <p class="mt-3 max-w-md text-sm leading-relaxed text-dark/60">
            Halaman ini hanya untuk admin. Jika kamu merasa ini kesalahan, hubungi pengelola situs.
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ url('/') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-primary/25 transition hover:bg-primary-dark">
                Kembali ke Beranda
            </a>

            <a href="{{ auth()->user()?->isAdmin() ? route('admin.dashboard') : route('user.dashboard') }}"
                class="inline-flex items-center gap-2 rounded-xl border-2 border-primary/20 bg-white px-5 py-3 text-sm font-semibold text-primary transition hover:bg-[#f1ecff]">
                Ke Dashboard
            </a>
        </div>
    </div>
</body>

</html>
