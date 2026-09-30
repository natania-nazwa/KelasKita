{{--
    Ilustrasi kartu kutip: buku bertumpuk, topi wisuda, dan pensil.

    Dipakai di kartu branding "Konten berkualitas, untuk pembelajaran
    yang lebih baik." Kartu itu dekoratif, jadi ilustrasinya tidak
    perlu punya teks di dalamnya: cukup terbaca sebagai buku bertumpuk
    dan topi wisuda dari bentuknya saja.
--}}

<svg viewBox="0 0 260 170" role="img"
    aria-label="Ilustrasi buku bertumpuk, topi wisuda, dan pensil">

    {{-- Lingkaran dekoratif. --}}
    <circle cx="132" cy="82" r="66" fill="#FFFFFF" opacity="0.5" />

    {{-- Buku-buku bertumpuk, miring sedikit supaya tidak terlihat kaku. --}}
    <g transform="rotate(-6 130 120)">
        <rect x="86" y="104" width="88" height="18" rx="5" fill="#6D4AFF" />
        <rect x="93" y="86" width="80" height="18" rx="5" fill="#8FD9C0" />
        <rect x="86" y="68" width="72" height="18" rx="5" fill="#B9A6FF" />

        {{-- Halaman buku: garis tipis putih di atas sampul. --}}
        <g fill="#FFFFFF" opacity="0.7">
            <rect x="93" y="111" width="42" height="3" rx="1.5" />
            <rect x="101" y="93" width="44" height="3" rx="1.5" />
            <rect x="93" y="75" width="34" height="3" rx="1.5" />
        </g>
    </g>

    {{-- Buku terbuka di atas tumpukan. --}}
    <g transform="translate(150 46)">
        <path d="M0 8c0-2 1.6-3.6 3.6-3.6h16.8c1.6 0 2.9.9 3.6 2.2.7-1.3 2-2.2 3.6-2.2h16.8c2 0 3.6 1.6 3.6 3.6V30c0 2-1.6 3.6-3.6 3.6H27.6c-1.6 0-2.9-.9-3.6-2.2-.7 1.3-2 2.2-3.6 2.2H3.6C1.6 33.6 0 32 0 30z"
            fill="#FFFFFF" />
        <path d="M24 6.6V31.4" stroke="#DDD5FF" stroke-width="2" stroke-linecap="round" />
        <g stroke="#C9B8FF" stroke-width="2" stroke-linecap="round">
            <path d="M6 14h12" />
            <path d="M6 20h10" />
            <path d="M30 14h12" />
            <path d="M30 20h10" />
        </g>
    </g>

    {{-- Topi wisuda melayang di atas buku. --}}
    <g transform="translate(64 22)">
        <path d="M0 22 34 6l34 16-34 16z" fill="#4C2FB3" />
        <path d="M0 22 34 6l34 16-34 16z" fill="none" stroke="#FFFFFF" stroke-width="1.5"
            stroke-linejoin="round" opacity="0.4" />
        <path d="M14 30v10c0 5 9 9 20 9s20-4 20-9V30l-20 9z" fill="#6D4AFF" />
        <path d="M68 24v14" stroke="#4C2FB3" stroke-width="3" stroke-linecap="round" />
        <circle cx="68" cy="41" r="4" fill="#F4A340" />
    </g>

    {{-- Pensil miring. --}}
    <g transform="rotate(28 218 128)">
        <rect x="210" y="92" width="11" height="52" rx="3" fill="#F4A340" />
        <rect x="210" y="92" width="11" height="15" rx="3" fill="#E96B83" />
        <path d="M210 144h11l-5.5 11z" fill="#F7DFC0" />
        <path d="M213 150h5l-2.5 5z" fill="#2B2350" />
    </g>

    {{-- Kilau kecil. --}}
    <g fill="#A88CFF">
        <path d="M232 40c0-1.1.9-2 2-2h2.4l1.2-2.4c.2-.4.6-.6 1-.6h3.8c.4 0 .8.2 1 .6l1.2 2.4H246c1.1 0 2 .9 2 2v3.2c0 1.1-.9 2-2 2h-2.4l-1.2 2.4c-.2.4-.6.6-1 .6h-3.8c-.4 0-.8-.2-1-.6l-1.2-2.4H234c-1.1 0-2-.9-2-2z" />
        <path d="M24 96c0-1.1.9-2 2-2h2.4l1.2-2.4c.2-.4.6-.6 1-.6h3.8c.4 0 .8.2 1 .6l1.2 2.4H38c1.1 0 2 .9 2 2v3.2c0 1.1-.9 2-2 2h-2.4l-1.2 2.4c-.2.4-.6.6-1 .6h-3.8c-.4 0-.8-.2-1-.6L27.4 99.2H26c-1.1 0-2-.9-2-2z" />
    </g>
</svg>
