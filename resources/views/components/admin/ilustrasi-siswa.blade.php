@props([
    /**
     * Tampilkan bunga kecil di sekeliling ilustrasi.
     *
     * Sengaja opsional dan mati secara bawaan: ilustrasi ini dipakai
     * dua banner (dashboard dan Kelola Materi), dan bunga hanya bagian
     * dari desain banner dashboard.
     */
    'bunga' => false,
])

{{--
    Ilustrasi hero: seorang admin/siswa perempuan sedang memakai laptop.

    Digambar langsung sebagai SVG inline, bukan berkas gambar:
    project tidak punya folder ilustrasi, dan ilustrasi vektor yang
    digambar di sini ikut menyesuaikan warnanya dengan palet admin
    tanpa perlu file terpisah.

    Gaya: flat illustration, bentuk bulat, palet lavender/purple/putih.
    viewBox dibuat lebar (320x220) supaya ilustrasi tidak terlalu tinggi
    dan tidak mendorong banner jadi jauh lebih tinggi dari teksnya.
--}}

<svg viewBox="0 0 320 220" role="img"
    aria-label="Ilustrasi seorang perempuan sedang belajar memakai laptop di depan buku dan tanaman">

    {{-- Titik dekoratif di belakang. --}}
    <g fill="#DDD5FF" opacity="0.65">
        <circle cx="42" cy="46" r="7" />
        <circle cx="288" cy="38" r="5" />
        <circle cx="300" cy="150" r="8" />
    </g>

    {{-- Kilau empat sudut. --}}
    <g fill="#B9A6FF">
        <path
            d="M64 26c0-1.1.9-2 2-2h2.4l1.2-2.4c.2-.4.6-.6 1-.6h3.8c.4 0 .8.2 1 .6l1.2 2.4H79c1.1 0 2 .9 2 2v3.2c0 1.1-.9 2-2 2h-2.4L75.4 34c-.2.4-.6.6-1 .6h-3.8c-.4 0-.8-.2-1-.6L68.4 31.2H66c-1.1 0-2-.9-2-2z" />
        <path
            d="M258 148c0-1.1.9-2 2-2h2.4l1.2-2.4c.2-.4.6-.6 1-.6h3.8c.4 0 .8.2 1 .6l1.2 2.4H271c1.1 0 2 .9 2 2v3.2c0 1.1-.9 2-2 2h-2.4l-1.2 2.4c-.2.4-.6.6-1 .6h-3.8c-.4 0-.8-.2-1-.6l-1.2-2.4H260c-1.1 0-2-.9-2-2z" />
    </g>

    {{-- Lingkaran besar di belakang karakter: memberi kedalaman tanpa
         menambahkan elemen baru. --}}
    <circle cx="168" cy="118" r="82" fill="#EDE7FF" />
    <circle cx="168" cy="118" r="82" fill="none" stroke="#DDD5FF" stroke-width="2" stroke-dasharray="5 7" />

    {{-- Tanaman kecil di kiri. --}}
    <g>
        <path d="M74 176h30l-5 30H79z" fill="#C9B8FF" />
        <path d="M89 176c-14 0-22-10-22-22 13 0 22 9 22 22z" fill="#8FD9C0" />
        <path d="M89 176c14 0 22-10 22-22-13 0-22 9-22 22z" fill="#B6E7D6" />
        <path d="M89 178c0-12 4-20 4-20s4 8 4 20z" fill="#6FC7A6" />
    </g>

    {{-- Karakter. --}}
    <g>
        {{-- Rambut belakang. --}}
        <path d="M126 74c0-19 15-32 34-32s34 13 34 32c0 14-3 24-7 30l-2 20h-50l-2-20c-4-6-7-16-7-30z"
            fill="#4C2FB3" />

        {{-- Badan / blus. --}}
        <path d="M132 138h56c10 0 18 8 19 18l4 34H109l4-34c1-10 9-18 19-18z" fill="#6D4AFF" />

        {{-- Leher. --}}
        <path d="M148 118h24v18c0 5-5 8-12 8s-12-3-12-8z" fill="#F2C9B0" />

        {{-- Wajah. --}}
        <path d="M160 44c16 0 27 11 27 27s-11 27-27 27-27-11-27-27 11-27 27-27z" fill="#FBDCC6" />

        {{-- Rambut depan / poni. --}}
        <path d="M131 72c0-19 13-32 29-32s29 13 29 32c0 2-.3 4-.8 5-3-14-13-21-28.2-21S135 63 134 77c-.5-1-1-3-1-5z"
            fill="#5A38D6" />
        <path d="M160 40c-8 10-11 22-11 32 0 3 .3 5 1 7 6-14 18-23 34-21-4-11-13-18-24-18z"
            fill="#4C2FB3" opacity="0.55" />

        {{-- Mata. --}}
        <g fill="#2B2350">
            <circle cx="150" cy="74" r="3" />
            <circle cx="171" cy="74" r="3" />
        </g>
        <g fill="#FFFFFF" opacity="0.9">
            <circle cx="151.2" cy="72.8" r="1.1" />
            <circle cx="172.2" cy="72.8" r="1.1" />
        </g>

        {{-- Pipi. --}}
        <g fill="#F7A9A9" opacity="0.55">
            <ellipse cx="143" cy="83" rx="4.5" ry="3" />
            <ellipse cx="178" cy="83" rx="4.5" ry="3" />
        </g>

        {{-- Senyum. --}}
        <path d="M155 85c3 2.5 10 2.5 13 0" fill="none" stroke="#C4766B" stroke-width="2"
            stroke-linecap="round" />
    </g>

    {{-- Laptop: ada di depan badan, jadi digambar setelah karakter. --}}
    <g>
        <path d="M196 148h84c3 0 5 2 5 5v33H191v-33c0-3 2-5 5-5z" fill="#E6E0FF" />
        <path d="M196 148h84c3 0 5 2 5 5v33H191v-33c0-3 2-5 5-5z" fill="none" stroke="#C9B8FF"
            stroke-width="2" />

        {{-- Isi layar: baris teks + kartu kecil, dibaca sebagai UI. --}}
        <g stroke="#A88CFF" stroke-width="3" stroke-linecap="round">
            <path d="M201 160h30" />
            <path d="M201 168h44" />
            <path d="M201 176h24" />
        </g>
        <rect x="253" y="157" width="22" height="21" rx="5" fill="#8FD9C0" />
        <path d="M258 168l4 4 8-8" fill="none" stroke="#FFFFFF" stroke-width="2.5"
            stroke-linecap="round" stroke-linejoin="round" />

        {{-- Basis laptop. --}}
        <path d="M183 186h110l6 8c1 1.5 0 4-2 4H179c-2 0-3-2.5-2-4z" fill="#C9B8FF" />
        <rect x="228" y="188" width="20" height="3" rx="1.5" fill="#A88CFF" />
    </g>

    {{-- Buku bertumpuk di kanan bawah. --}}
    <g>
        <rect x="238" y="176" width="46" height="11" rx="3" fill="#6D4AFF" />
        <rect x="243" y="165" width="38" height="11" rx="3" fill="#8FD9C0" />
        <rect x="238" y="154" width="30" height="11" rx="3" fill="#F4A340" />
        <g fill="#FFFFFF" opacity="0.65">
            <rect x="243" y="179" width="20" height="2" rx="1" />
            <rect x="249" y="168" width="20" height="2" rx="1" />
            <rect x="243" y="157" width="14" height="2" rx="1" />
        </g>
    </g>

    {{-- Pensil miring di kiri bawah. --}}
    <g transform="rotate(-24 52 178)">
        <rect x="44" y="168" width="9" height="40" rx="3" fill="#F4A340" />
        <rect x="44" y="168" width="9" height="12" rx="3" fill="#E96B83" />
        <path d="M44 208h9l-4.5 9z" fill="#F7DFC0" />
        <path d="M46.6 213.4h3.8l-1.9 3.6z" fill="#2B2350" />
    </g>

    {{-- Bunga kecil: dekorasi tambahan untuk banner dashboard. --}}
    @if ($bunga)
        <g aria-hidden="true">
            <g transform="translate(30 104)">
                <g fill="#FFC7DE">
                    <circle cx="0" cy="-6.5" r="4.4" />
                    <circle cx="6.1" cy="-2" r="4.4" />
                    <circle cx="3.8" cy="5.3" r="4.4" />
                    <circle cx="-3.8" cy="5.3" r="4.4" />
                    <circle cx="-6.1" cy="-2" r="4.4" />
                </g>
                <circle cx="0" cy="0" r="3.1" fill="#F4A340" />
            </g>

            <g transform="translate(288 92) scale(0.82)">
                <g fill="#D6CCFF">
                    <circle cx="0" cy="-6.5" r="4.4" />
                    <circle cx="6.1" cy="-2" r="4.4" />
                    <circle cx="3.8" cy="5.3" r="4.4" />
                    <circle cx="-3.8" cy="5.3" r="4.4" />
                    <circle cx="-6.1" cy="-2" r="4.4" />
                </g>
                <circle cx="0" cy="0" r="3.1" fill="#FFFFFF" />
            </g>

            <g fill="#B9A6FF" opacity="0.75">
                <circle cx="46" cy="86" r="2.6" />
                <circle cx="272" cy="76" r="2.2" />
                <circle cx="20" cy="124" r="2" />
            </g>
        </g>
    @endif
</svg>
