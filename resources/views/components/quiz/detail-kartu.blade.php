@props([
    // Kartu quiz dari App\Support\DaftarQuiz.
    'kartu',
    // Jumlah soal aktif, dihitung ulang dari daftar soal yang tampil supaya
    // angkanya tidak berbeda dengan bagian "Daftar Soal".
    'jumlahSoal' => 0,
    /*
     * Sesi lobby milik quiz ini yang masih hidup, kalau ada. Nilainya array
     * ['tautan' => ...] supaya komponen ini tidak terikat Eloquent; null
     * kalau belum ada sesi berjalan.
     */
    'sesiHost' => null,
    /*
     * Apakah tombol aksi pembaca dirender.
     *
     * true (bawaan) untuk halaman detail milik pengguna. false untuk
     * admin.quiz-detail: "Mulai Quiz", "Bagikan", dan form buka sesi adalah
     * milik orang yang akan mengerjakan quiz, bukan milik admin yang sedang
     * memeriksa isinya. Yang boleh ditampilkan di sana tetap identitas quiz
     * dan statusnya, jadi hanya blok aksi yang dimatikan.
     */
    'aksi' => true,
])

@php
    /*
     * Kartu utama halaman detail quiz: banner, lencana kategori, judul,
     * metadata, deskripsi, dan dua tombol aksi.
     *
     * Sumber datanya array polos dari App\Support\DaftarQuiz, sama seperti
     * kartu di halaman daftar, jadi tidak terikat Eloquent.
     *
     * Bentuk array yang dipakai:
     *   id, judul, deskripsi, thumbnail, durasi, jumlah_soal,
     *   tingkat_kesulitan, status, status_label, visibilitas, pakai_kode,
     *   kode_akses, saya, tautan_mulai,
     *   kategori => [nama, ikon, warna, warna_gelap],
     *   pembuat  => [nama, inisial, warna, warna_gelap]
     */
    $kategori = $kartu['kategori'];
    $pembuat = $kartu['pembuat'];
    $kesulitan = filled($kartu['tingkat_kesulitan'] ?? null) ? $kartu['tingkat_kesulitan'] : 'Mudah';
@endphp

<article {{ $attributes->class(['detail-quiz__kartu', 'kartu-detail']) }}
    style="--k: {{ $kategori['warna'] }}; --k-gelap: {{ $kategori['warna_gelap'] }};">

    {{--
        A. Banner quiz.

        Lebar penuh mengikuti card, tinggi tetap (bukan perbandingan), supaya
        posisinya sama persis seperti rancangan: 84px di ponsel, 100px mulai
        tablet. Foto memakai object-cover; kalau foto tidak ada, atau gagal
        dimuat (mis. perangkat tanpa internet), yang terlihat hanya gradasi
        warna kategori, jadi banner tidak pernah kosong.
    --}}
    <div class="detail-quiz__banner">
        @if (filled($kartu['thumbnail'] ?? null))
            <img src="{{ $kartu['thumbnail'] }}" alt="" class="detail-quiz__banner-foto"
                data-detail-banner loading="lazy">
        @else
            <span class="detail-quiz__banner-ikon" aria-hidden="true">{{ $kategori['ikon'] }}</span>
        @endif
    </div>

    <div class="detail-quiz__badan">

        {{-- B. Lencana kategori, ditambah lencana status / privat yang
                masih dibutuhkan supaya pemilik tahu kondisinya. --}}
        <div class="flex flex-wrap items-center gap-2">
            <span class="lencana bg-lavender text-primary-dark">
                <span class="mr-1.5" aria-hidden="true">{{ $kategori['ikon'] }}</span>

                {{ $kategori['nama'] }}
            </span>

            @if (($kartu['status'] ?? '') !== \App\Models\Quiz::STATUS_PUBLISHED)
                <span class="lencana bg-lavender text-dark/55">{{ $kartu['status_label'] }}</span>
            @endif

            @if (($kartu['visibilitas'] ?? '') === \App\Models\Quiz::VISIBILITAS_PRIVAT)
                <span class="lencana bg-brand-bg text-dark/55">Privat</span>
            @endif
        </div>

        {{-- C. Identitas quiz. --}}
        <h1 class="detail-quiz__judul">{{ $kartu['judul'] }}</h1>

        <ul class="detail-quiz__meta">
            <li>
                Dibuat oleh
                <strong>{{ $pembuat['nama'] }}</strong>
            </li>

            <li>{{ $jumlahSoal }} Soal</li>

            <li>Tingkat {{ $kesulitan }}</li>
        </ul>

        @if (filled($kartu['deskripsi']))
            <p class="detail-quiz__deskripsi">{{ $kartu['deskripsi'] }}</p>
        @endif

        {{--
            D. Dua tombol aksi. "Mulai Quiz" adalah CTA utama halaman ini,
            jadi ia dibuat setinggi tombol kedua dan sama-sama melebar rata,
            bukan menumpuk. Di layar sangat sempit keduanya turun ke bawah
            supaya teks dan ikon tidak pernah terpotong.

            admin.quiz-detail mengirim $aksi=false, jadi blok ini tidak
            dirender di sana: admin tidak akan mengerjakan quiz ini.
        --}}
        @if ($aksi)
            <div class="detail-quiz__aksi">
                <a href="{{ $kartu['tautan_mulai'] }}" class="detail-quiz__mulai">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                    </svg>

                    Mulai Quiz
                </a>

                <button type="button" class="detail-quiz__bagikan" data-bagikan-buka
                    aria-label="Bagikan quiz {{ $kartu['judul'] }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                    </svg>

                    Bagikan
                </button>
            </div>

            {{--
                Quiz mode kode milik pengguna yang sedang login bisa dijalankan
                bersama-sama: orang lain masuk lewat kode quiz ini, bukan lewat
                tombol di atas. Jadisnya sengaja diletakkan sebagai catatan kecil
                di bawah aksi, supaya tidak bersaing dengan CTA utama tapi tidak
                hilang juga.
            --}}
            @if (($kartu['saya'] ?? false) && $jumlahSoal > 0 && ($kartu['pakai_kode'] ?? false))
                {{--
                    Div, bukan <p>: di dalam bagian ini ada <form> buat sesi,
                    sedangkan <p> hanya boleh berisi teks dan elemen sebaris.
                --}}
                <div class="detail-quiz__host">
                    <span>
                        Ingin menguji bersama teman? Bagikan kode
                        <strong>{{ $kartu['kode_akses'] }}</strong> ke mereka.
                    </span>

                    @if ($sesiHost !== null)
                        <a href="{{ $sesiHost['tautan'] }}" class="detail-quiz__host-tautan">
                            Buka lobby
                        </a>
                    @else
                        <form method="POST" action="{{ $kartu['tautan_mulai_sesi'] }}" class="inline">
                            @csrf

                            <button type="submit" class="detail-quiz__host-tautan">
                                Buka Sesi
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        @endif
    </div>
</article>
