@props ([
    'judul',
    'ikon',
])

{{--
    Kepala kartu pengaturan: ikon ungu di kiri, judul di kanan.

    Dipakai oleh kartu "Informasi Akun", "Keamanan", dan "Tampilan"
    supaya ketiganya punya pembuka yang sama persis.
--}}
<div class="flex items-center gap-3">
    <span class="profil-kartu__ikon" aria-hidden="true">
        <svg
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="{{ \App\Support\Ikon::path($ikon) }}"
            />
        </svg>
    </span>

    <h2 class="profil-kartu__judul">{{ $judul }}</h2>
</div>
