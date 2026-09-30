{{--
    Ilustrasi hero halaman "Materi": admin perempuan di belakang meja dengan
    laptop, papan klip, buku, dan tanaman.

    Sepadan dengan ilustrasi-buku: digambar sendiri, tanpa pustaka ikon,
    supaya warnanya bisa persis mengikuti palet admin (ungu #6D4AFF, ungu tua
    #4C2FB3, lavender #DDD5FF, dan hijau #8FD9C0).

    Murni dekoratif, jadi tidak ada teks di dalamnya: peran ilustrasi ini
    cuma mengisi sisi kanan banner, dan teks yang perlu dibaca admin ada di
    sebelah kirinya. Karena itu elemennya diberi aria-hidden di dalam
    komponen ini, bukan role="img" seperti ilustrasi-buku yang punya label.
--}}

<svg viewBox="0 0 220 130" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">

    {{-- Lingkaran latar. --}}
    <circle cx="112" cy="66" r="56" fill="#FFFFFF" opacity="0.5" />

    {{--Tanaman di kiri. --}}
    <g transform="translate(10 52)">
        <path d="M14 44C14 30 12 20 8 10" stroke="#4C2FB3" stroke-width="2.5" stroke-linecap="round" />
        <path d="M20 44C20 32 24 24 32 18" stroke="#4C2FB3" stroke-width="2.5" stroke-linecap="round" />
        <ellipse cx="6" cy="10" rx="9" ry="6" transform="rotate(-32 6 10)" fill="#8FD9C0" />
        <ellipse cx="33" cy="17" rx="10" ry="6.5" transform="rotate(-26 33 17)" fill="#4FD0A8" />
        <path d="M2 44h32l-4 22H6z" fill="#B9A6FF" />
        <rect x="0" y="41" width="36" height="6" rx="3" fill="#6D4AFF" />
    </g>

    {{-- Admin: tubuh dan lengan di belakang laptop. --}}
    <g transform="translate(78 20)">
        {{-- Rambut panjang. --}}
        <path d="M28 34c0-13 8-22 18-22s18 9 18 22v10H28z" fill="#3B2C6B" />
        <path d="M28 30c-3 4-4 10-4 16 0 6 2 10 5 12 1-10 1-20-1-28z" fill="#3B2C6B" />
        <path d="M64 30c3 4 4 10 4 16 0 6-2 10-5 12-1-10-1-20 1-28z" fill="#3B2C6B" />

        {{-- Wajah. --}}
        <circle cx="46" cy="32" r="14" fill="#F6C9A8" />
        <path d="M32 26c2-9 8-13 14-13s12 4 14 13c-4-5-9-7-14-7s-10 2-14 7z" fill="#3B2C6B" />

        {{-- Mata dan senyum. --}}
        <circle cx="40.5" cy="32" r="1.4" fill="#3B2C6B" />
        <circle cx="51.5" cy="32" r="1.4" fill="#3B2C6B" />
        <path d="M42 38.5c2.2 2 5.8 2 8 0" stroke="#B4705A" stroke-width="1.4" stroke-linecap="round" />

        {{-- Leher dan blus. --}}
        <path d="M42 44h8v6h-8z" fill="#E8B291" />
        <path d="M30 74c0-9 7-16 16-16s16 7 16 16v10H30z" fill="#6D4AFF" />
        <path d="M38 60l8 8 8-8" stroke="#4C2FB3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />

        {{-- Lengan kanan menyentuh papan klip. --}}
        <path d="M60 68c6 2 10 5 12 9" stroke="#6D4AFF" stroke-width="5" stroke-linecap="round" />
    </g>

    {{-- Papan klip di tangan admin. --}}
    <g transform="translate(160 44) rotate(8)">
        <rect x="0" y="0" width="34" height="44" rx="5" fill="#FFFFFF" />
        <rect x="10" y="-4" width="14" height="9" rx="3" fill="#4C2FB3" />
        <g stroke="#C9BCFF" stroke-width="2.5" stroke-linecap="round">
            <path d="M7 12h20" />
            <path d="M7 20h20" />
            <path d="M7 28h13" />
        </g>
        <path d="M7 35.5l4 4 7-8" stroke="#35B779" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
    </g>

    {{-- Laptop di meja. --}}
    <g transform="translate(96 84)">
        <path d="M4 0h56a4 4 0 0 1 4 4v34H0V4a4 4 0 0 1 4-4z" fill="#4C2FB3" />
        <path d="M6 4h52v27H6z" fill="#DDD5FF" />
        <path d="M12 12h30" stroke="#6D4AFF" stroke-width="3" stroke-linecap="round" />
        <path d="M12 20h20" stroke="#B9A6FF" stroke-width="3" stroke-linecap="round" />
        <path d="M-6 38h76l-4 6H-2z" fill="#6D4AFF" />
    </g>

    {{-- Buku bertumpuk di kanan meja. --}}
    <g transform="translate(178 92)">
        <rect x="0" y="0" width="34" height="8" rx="3" fill="#6D4AFF" />
        <rect x="3" y="9" width="30" height="8" rx="3" fill="#8FD9C0" />
        <rect x="1" y="18" width="32" height="8" rx="3" fill="#B9A6FF" />
    </g>

    {{-- Kilau kecil. --}}
    <g fill="#A88CFF">
        <path d="M46 6c0-1.1.9-2 2-2h2.4l1.2-2.4c.2-.4.6-.6 1-.6h3.8c.4 0 .8.2 1 .6L58.6 4h2.4c1.1 0 2 .9 2 2v3.2c0 1.1-.9 2-2 2H61l-1.2 2.4c-.2.4-.6.6-1 .6h-3.8c-.4 0-.8-.2-1-.6L52.8 11H48c-1.1 0-2-.9-2-2z" />
        <path d="M204 30c0-.9.7-1.6 1.6-1.6h1.9l1-1.9c.2-.3.5-.5.8-.5h3c.3 0 .6.2.8.5l1 1.9h1.9c.9 0 1.6.7 1.6 1.6v2.6c0 .9-.7 1.6-1.6 1.6H215l-1 1.9c-.2.3-.5.5-.8.5h-3c-.3 0-.6-.2-.8-.5l-1-1.9h-1.9c-.9 0-1.6-.7-1.6-1.6z" />
    </g>
</svg>
