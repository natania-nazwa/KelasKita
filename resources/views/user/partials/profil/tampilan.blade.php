{{--
    Kartu "Tampilan": pilihan mode terang / gelap.

    Yang disimpan bukan lewat server, melainkan localStorage dengan kunci
    "kk-tema". Alasannya, tema menyangkut seluruh tampilan aplikasi,
    jadi harus berlaku sebelum halaman pertama selesai dimuat; itu
    sudah ditangani oleh script kecil di <head> layouts/app.blade.php.

    Tombol yang aktif ditandai dengan aria-pressed, bukan hanya dengan
    warna, supaya status ini terbaca juga oleh pembaca layar. Nilai
    aria-pressed saat halaman dibuka diset oleh profil.js supaya selalu
    cocok dengan tema yang benar-benar sedang dipakai.
--}}

<section
    data-reveal
    style="--reveal-delay: 280ms"
    class="profil-kartu mt-5 p-5 sm:p-6"
    aria-labelledby="profil-judul-tampilan"
>
    <div
        class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="min-w-0">
            <x-profil.kepala judul="Tampilan" ikon="matahari" />

            <p class="profil-kartu__deskripsi">Sesuaikan tampilan Kelas Kita dengan preferensimu.</p>
        </div>

        {{--
            Segmented control: dua pilihan dalam satu rel. Di ponsel
            melebar penuh (flex-1 di dalam .tema-pilih__item) supaya
            tetap mudah diketuk dan tidak squeezing teksnya.
        --}}
        <div
            class="tema-pilih w-full shrink-0 sm:w-auto"
            role="group"
            aria-label="Mode tampilan"
            data-tema-pilih
        >
            @foreach ([
                'terang' => ['label' => 'Terang', 'ikon' => 'matahari'],
                'gelap' => ['label' => 'Gelap', 'ikon' => 'bulan'],
            ] as $nilai => $pilihan)
                <button
                    type="button"
                    data-tema="{{ $nilai }}"
                    aria-pressed="false"
                    class="tema-pilih__item"
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
                            d="{{ \App\Support\Ikon::path($pilihan['ikon']) }}"
                        />
                    </svg>

                    {{ $pilihan['label'] }}
                </button>
            @endforeach
        </div>
    </div>
</section>
