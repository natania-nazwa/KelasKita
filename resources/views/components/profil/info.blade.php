@props ([
    /*
     * Nama ikon di App\Support\Ikon. Baris informasi di halaman Profil
     * semuanya memakai ikon yang sama supaya penanda di sebelah kiri
     * terlihat seragam.
     */
    'ikon',

    'label',
    'nilai',
])

{{--
    Satu baris informasi akun: ikon ungu di kiri, label kecil di atas,
    nilai tebal di bawahnya.

    min-w-0 + truncate di .profil-info__nilai yang penting: nama dan
    email bisa jauh lebih panjang dari lebar kartu di layar ponsel, dan
    tanpa itu halaman ikut melebar (horizontal overflow).
--}}
<div class="profil-info">
    <span class="profil-info__ikon" aria-hidden="true">
        <svg
            class="h-[1.15rem] w-[1.15rem]"
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

    <div class="min-w-0 flex-1">
        <dt class="profil-info__label">{{ $label }}</dt>

        <dd class="profil-info__nilai" title="{{ $nilai }}">{{ $nilai }}</dd>
    </div>
</div>
