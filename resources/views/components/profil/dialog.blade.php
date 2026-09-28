@props ([
    /*
     * Identitas dialog untuk aria dan untuk atribut yang dibaca
     * JavaScript pemanggilnya. Nilai ini juga jadi id aria-labelledby.
     */
    'id' => 'dialog',

    'judul',
    'pesan' => null,
    'ikon' => null,

    /*
     * Dialog yang isinya tentang menghapus sesuatu memakai warna merah
     * pada ikon dan tombolnya. Halaman Profil hanya punya dua dialog
     * seperti ini, jadi cukup satu penanda, bukan kelas utility yang
     * ditulis manual di tiap pemanggil.
     */
    'bahaya' => false,
])

{{--
    Kerangka dialog untuk halaman Profil.

    Cara buka/tutup sengaja sama persis dengan .dialog-bab (hapus bab di
    halaman Tambah Materi): display:none sebagai keadaan awal, lalu kelas
    .is-buka membuatnya flex. Keuntungannya, kalau JavaScript mati
    dialog tetap tidak pernah menutupi halaman, dan tidak ada form yang
    terkirim diam-diam karena tombol pemicunya type="button".

    Panel memakai max-width plus margin auto, dan .modal punya
    overflow-y:auto, jadi di layar sempit dialog tinggi ikut bergulir
    sendiri dan tidak memaksa halaman melebar.
--}}
<div
    class="modal"
    data-dialog="{{ $id }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-judul"
    @if ($pesan) aria-describedby="{{ $id }}-pesan" @endif
>
    <div class="modal__panel">
        <div class="flex items-start gap-3 px-5 pt-5 sm:px-6 sm:pt-6">
            @if ($ikon)
                <span
                    @class ([
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                    'bg-[#fdecee] text-[#c2414a]' => $bahaya,
                    'bg-lavender/60 text-primary' => ! $bahaya,
                ])
                    aria-hidden="true"
                >
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
                            d="{{ $ikon }}"
                        />
                    </svg>
                </span>
            @endif

            <div class="min-w-0 flex-1">
                <h2
                    id="{{ $id }}-judul"
                    class="text-base font-extrabold text-dark"
                >
                    {{ $judul }}
                </h2>

                @if ($pesan)
                    <p
                        id="{{ $id }}-pesan"
                        class="mt-1.5 text-sm leading-relaxed text-dark/60"
                    >
                        {{ $pesan }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Isi form, atau tidak ada apa-apa untuk dialog konfirmasi sederhana. --}}
        @isset ($isi)
            <div class="px-5 pt-5 sm:px-6">{{ $isi }}</div>
        @endisset

        <div class="px-5 pb-5 pt-5 sm:px-6 sm:pb-6">{{ $aksi }}</div>
    </div>
</div>
