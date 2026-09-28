{{--
    Kartu kepala halaman Profil: avatar lingkaran, nama, email, tanggal
    bergabung, dan tombol Edit Profil.

    Avatar diambil dari x-profil.avatar, yang otomatis memilih antara
    foto profil dan inisial nama depan. Karena seluruh data di sini
    berasal dari $pengguna, begitu nama atau foto berubah lewat dialog
    Edit Profil, kartu ini langsung ikut memakai data terbaru begitu
    halaman dimuat ulang.

    Susunan mobile: avatar di atas, teks di tengah, tombol full width di
    bawah. Di layar lebar semuanya jadi satu baris. Tombol memakai
    w-full di mobile supaya tidak pernah keluar layar.
--}}

@php
    $bergabung = $pengguna->created_at?->translatedFormat('d F Y') ?? '-';
@endphp

<div
    data-reveal
    style="--reveal-delay: 100ms"
    class="profil-kepala mt-6 p-5 sm:p-6"
>
    <div
        class="flex flex-col items-center gap-5 text-center sm:flex-row sm:gap-6 sm:text-left"
    >
        <x-profil.avatar :pengguna="$pengguna" />

        <div class="min-w-0 flex-1">
            <h2
                class="truncate text-xl font-extrabold tracking-tight text-dark sm:text-2xl"
            >
                {{ $pengguna->nama }}
            </h2>

            <p
                class="mt-1 flex items-center justify-center gap-1.5 text-sm text-dark/55 sm:justify-start"
            >
                <svg
                    class="h-4 w-4 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="{{ \App\Support\Ikon::path('surel') }}"
                    />
                </svg>

                <span class="truncate">{{ $pengguna->email }}</span>
            </p>

            <p
                class="mt-2.5 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-xs text-dark/50 sm:justify-start"
            >
                <span class="inline-flex items-center gap-1.5">
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="{{ \App\Support\Ikon::path('kalender') }}"
                        />
                    </svg>

                    Bergabung sejak {{ $bergabung }}
                </span>

                {{-- Peran hanya dibaca, tidak bisa diubah dari halaman ini. --}}
                <span
                    class="rounded-full bg-lavender px-2.5 py-0.5 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-primary"
                >
                    {{ $pengguna->isAdmin() ? 'Admin' : 'Siswa' }}
                </span>
            </p>
        </div>

        {{--
            Dua aksi, ditumpuk di mobile dan diberi lebar penuh supaya
            tidak ada tombol yang terpotong layar sempit.
        --}}
        <div class="flex w-full shrink-0 flex-col gap-2 sm:w-auto">
            <button
                type="button"
                data-dialog-buka="dialog-edit-profil"
                class="tombol-utama w-full sm:w-auto"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="{{ \App\Support\Ikon::path('pena') }}"
                    />
                </svg>

                Edit Profil
            </button>

            {{--
                Tombol ini hanya dirender kalau memang ada foto. Kalau
                tidak ada, tidak ada yang perlu dihapus dan avatar sudah
                berupa inisial.
            --}}
            @if (filled($pengguna->foto_profil))
                <button
                    type="button"
                    data-dialog-buka="dialog-hapus-foto"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-lavender bg-white px-4 py-2 text-xs font-bold text-[#c2414a] transition hover:border-[#c2414a] hover:bg-[#fdecee] sm:w-auto"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="{{ \App\Support\Ikon::path('sampah') }}"
                        />
                    </svg>

                    Hapus Foto
                </button>
            @endif
        </div>
    </div>
</div>
