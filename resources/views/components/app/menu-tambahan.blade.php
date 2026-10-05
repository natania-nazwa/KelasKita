@props([
    /*
     * Menu tambahan untuk layar kecil: item dari array $menu di layout
     * yang TIDAK ikut navigasi bawah (Jadwal, Hasil, Simpan) plus tautan
     * Admin kalau peran pengguna berlaku. Bentuknya sama dengan item
     * sidebar, jadi 'route', 'pola', 'label', dan 'ikon' semuanya ikut.
     */
    'daftar' => [],
    /*
     * Tautan "Dashboard Admin", sama seperti sidebar. Proteksi
     * sesungguhnya ada di middleware "admin" — ini hanya tampilan.
     */
    'admin' => false,
])

{{--
    Tombol "Menu lainnya".

    Header mobile hanya punya dua baris, jadi strip chip horizontal yang
    dulu menempel di bawah logo dipadatkan jadi satu tombol. Bentuknya
    <details> supaya tetap terbuka-tutup tanpa JavaScript, sama seperti
    lonceng notifikasi dan chip akun di sebelahnya.
--}}
<details class="group/lain relative shrink-0">
    <summary
        class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full border border-lavender bg-white text-dark/60 transition hover:border-primary hover:text-primary [&::-webkit-details-marker]:hidden"
        aria-label="Menu lainnya" title="Menu lainnya">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3.75 6A2.25 2.25 0 1 1 3.75 10.5 2.25 2.25 0 0 1 3.75 6Zm6.75 0A2.25 2.25 0 1 1 10.5 10.5 2.25 2.25 0 0 1 10.5 6Zm6.75 0A2.25 2.25 0 1 1 20.25 10.5 2.25 2.25 0 0 1 20.25 6ZM3.75 13.5A2.25 2.25 0 1 1 3.75 18 2.25 2.25 0 0 1 3.75 13.5Zm6.75 0A2.25 2.25 0 1 1 10.5 18 2.25 2.25 0 0 1 10.5 13.5Zm6.75 0A2.25 2.25 0 1 1 20.25 18 2.25 2.25 0 0 1 20.25 13.5Z" />
        </svg>
    </summary>

    {{-- right-0 + top-full: panel turun dari tombolnya sendiri, dan
         lebarnya dibatasi viewport supaya tidak keluar layar. --}}
    <div
        class="absolute right-0 top-full z-40 mt-2 w-56 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-lavender bg-white p-1.5 shadow-[0_24px_44px_-28px_rgba(33,26,58,0.6)]">
        <p class="px-3 pb-1 pt-2 font-mono text-[10px] font-semibold tracking-[0.14em] text-dark/40 uppercase">
            Menu Lainnya
        </p>

        @foreach ($daftar as $item)
            @php
                $aktif = collect($item['pola'] ?? [])
                    ->contains(fn (string $pola) => request()->routeIs($pola));
            @endphp

            <a href="{{ route($item['route']) }}"
                @class([
                    'flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                    'bg-lavender text-primary-dark' => $aktif,
                    'text-dark/75 hover:bg-lavender hover:text-primary-dark' => ! $aktif,
                ])>
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['ikon'] }}" />
                </svg>

                {{ $item['label'] }}
            </a>
        @endforeach

        {{-- Menu admin hanya dirender kalau peran di database = admin.
             Ini PUNYA TAMPILAN saja; proteksi sesungguhnya ada di
             middleware "admin" (App\Http\Middleware\EnsureAdmin). --}}
        @if ($admin)
            <div class="mx-3 my-1.5 border-t border-lavender" aria-hidden="true"></div>

            <a href="{{ route('admin.dashboard') }}"
                @class([
                    'flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                    'bg-lavender text-primary-dark' => request()->routeIs('admin.*'),
                    'text-dark/75 hover:bg-lavender hover:text-primary-dark' => ! request()->routeIs('admin.*'),
                ])>
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.03 7.03 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>

                Dashboard Admin
            </a>
        @endif
    </div>
</details>
