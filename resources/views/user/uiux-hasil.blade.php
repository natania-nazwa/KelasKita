@extends('layouts.app')

@section('title', ($adaHasil ? 'Hasil Quiz' : 'Hasil Quiz Belum Tersedia') . ' | KelasKita')

@section('content')
    {{--
        Halaman hasil quiz dalam satu kartu besar: nilai, rincian jawaban,
        waktu pengerjaan, dan detail quiz. Semua angka berasal dari
        tb_pengerjaan_quiz lewat App\Support\DaftarHasil, tidak ada satupun
        yang ditulis langsung di sini.

        Menampilkan kerangka loading hanya bisa dilakukan lewat JavaScript,
        jadi atribut penandanya dipasang oleh skrip di bawah SEBELUM isi
        kartu diparse. Tanpa JavaScript atribut itu tidak pernah ada, isi
        kartu langsung tampil, dan tidak ada angka dummy yang muncul
        selagi halaman dimuat.
    --}}
    <script>
        document.documentElement.setAttribute('data-uiux-muat', '');

        window.addEventListener('load', function () {
            document.documentElement.removeAttribute('data-uiux-muat');
        });
    </script>

    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">

        <div class="mx-auto w-full max-w-6xl">

            {{-- ==================== TOMBOL KEMBALI ==================== --}}
            <a href="{{ route('user.quiz') }}" class="uiux-hasil__kembali">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kiri') }}" />
                </svg>

                Kembali ke Daftar Quiz
            </a>

            {{-- ==================== KERANGKA LOADING ====================
                 Hanya terlihat selama atribut data-uiux-muat masih ada di
                 <html>. Sengaja tidak memuat satu pun angka, supaya tidak
                 pernah ada nilai palsuan yang sempat terbaca. --}}
            <div class="uiux-hasil__muat" aria-hidden="true">
                <div class="uiux-hasil__rangka uiux-hasil__rangka--kepala"></div>

                <div class="uiux-hasil__susun">
                    <div class="space-y-5">
                        <div class="uiux-hasil__rangka h-56"></div>

                        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                            @for ($i = 0; $i < 4; $i++)
                                <div class="uiux-hasil__rangka h-32"></div>
                            @endfor
                        </div>

                        <div class="uiux-hasil__rangka h-16"></div>
                    </div>

                    <div class="uiux-hasil__rangka h-80"></div>
                </div>
            </div>

            {{-- ==================== KOSONG / BELUM ADA DATA ==================== --}}
            @unless ($adaHasil)
                <section data-reveal class="uiux-hasil__kosong">
                    <span class="uiux-hasil__kosong-ikon">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('dokumen') }}" />
                        </svg>
                    </span>

                    <h1 class="uiux-hasil__kosong-judul">Data hasil quiz tidak ditemukan</h1>

                    <p class="uiux-hasil__kosong-teks">
                        Silakan kembali ke daftar quiz dan coba lagi.
                    </p>

                    <a href="{{ route('user.quiz') }}" class="uiux-hasil__tombol uiux-hasil__tombol--utama">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kiri') }}" />
                        </svg>

                        Kembali ke Daftar Quiz
                    </a>
                </section>
            @else
                <div class="uiux-hasil__isi">
                    <div class="uiux-hasil__susun">

                        {{-- ==================== KOLOM UTAMA ==================== --}}
                        <div class="min-w-0 space-y-5">

                            {{-- Kepala: ilustrasi + judul. Horizontal di desktop,
                                 menumpuk di mobile supaya ilustrasi tidak
                                 membuat judul terpotong. --}}
                            <section data-reveal class="uiux-hasil__kartu uiux-hasil__kepala">
                                <span class="uiux-hasil__lencana">
                                    <svg class="h-9 w-9" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('piala') }}" />
                                    </svg>

                                    <svg class="uiux-hasil__kilau" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kilau') }}" />
                                    </svg>
                                </span>

                                <div class="min-w-0">
                                    {{-- Pengerjaan yang belum ditutup belum tentu
                                         "selesai", jadi judulnya ikut
                                         menyesuaikan. --}}
                                    <h1 class="uiux-hasil__judul">
                                        {{ $hasil['sudah_selesai'] ? 'Quiz Selesai!' : 'Quiz Belum Selesai' }}
                                    </h1>

                                    <p class="uiux-hasil__subjudul">
                                        @if ($hasil['sudah_selesai'])
                                            Hebat! Kamu sudah menyelesaikan semua soal dengan baik.
                                            Berikut adalah hasil pengerjaan quiz kamu.
                                        @else
                                            Quiz ini belum ditutup, jadi nilainya masih bisa berubah.
                                            Selesaikan dulu soal-soalnya ya.
                                        @endif
                                    </p>
                                </div>
                            </section>

                            {{-- ==================== NILAI ==================== --}}
                            <section data-reveal class="uiux-hasil__kartu uiux-hasil__nilai">
                                <p class="uiux-hasil__nilai-judul">Nilai Kamu</p>

                                <p class="uiux-hasil__nilai-angka">
                                    <span data-nilai-akhir>{{ $hasil['nilai'] }}</span><span class="uiux-hasil__nilai-skala">/100</span>
                                </p>

                                {{--
                                    Lencana di bawah angka memakai status yang
                                    sudah dihitung PengerjaanQuiz (ambang
                                    lulus 70), bukan predikat baru. Skala
                                    0-100 di sebelah angkanya supaya angka
                                    besar ini tidak pernah dibaca tanpa
                                    konteks.
                                --}}
                                <p class="uiux-hasil__predikat uiux-hasil__predikat--{{ $hasil['status'] }}">
                                    @if ($hasil['status'] === \App\Models\PengerjaanQuiz::STATUS_SELESAI)
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('bintang') }}" />
                                        </svg>
                                    @endif

                                    {{ $hasil['status_label'] }}
                                </p>

                                <p class="uiux-hasil__motivasi">
                                    @switch($hasil['status'])
                                        @case(\App\Models\PengerjaanQuiz::STATUS_SELESAI)
                                            Kamu sudah menjawab dengan cukup baik! Terus tingkatkan lagi ya.
                                            @break

                                        @case(\App\Models\PengerjaanQuiz::STATUS_GAGAL)
                                            Nilainya belum sampai ambang lulus. Yuk, pelajari materinya lagi lalu coba sekali lagi.
                                            @break

                                        @default
                                            Pengerjaanmu belum ditutup, jadi nilainya masih sementara.
                                    @endswitch
                                </p>

                                <p class="uiux-hasil__ambang">
                                    Ambang lulus: {{ \App\Models\PengerjaanQuiz::NILAI_LULUS }}
                                </p>
                            </section>

                            {{-- ==================== STATISTIK ====================
                                 Empat kartu, 2x2 di layar sempit lalu 4
                                 sejajar di desktop. Warna tidak pernah jadi
                                 satu-satunya penanda: tiap kartu punya
                                 ikon dan nama yang berbeda. --}}
                            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                                @php
                                    $statistik = [
                                        [
                                            'ikon' => 'centang',
                                            'label' => 'Jawaban Benar',
                                            'nilai' => (string) $hasil['jumlah_benar'],
                                            'keterangan' => 'dari '.$hasil['jumlah_soal'].' soal',
                                            'nada' => 'benar',
                                        ],
                                        [
                                            'ikon' => 'silang',
                                            'label' => 'Jawaban Salah',
                                            'nilai' => (string) $hasil['jumlah_salah'],
                                            'keterangan' => 'dari '.$hasil['jumlah_soal'].' soal',
                                            'nada' => 'salah',
                                        ],
                                        [
                                            'ikon' => 'jam',
                                            'label' => 'Waktu Pengerjaan',
                                            'nilai' => $hasil['durasi_label'],
                                            // Batas waktu hanya ditulis kalau quiz
                                            // punya durasi. Quiz tanpa durasi
                                            // tidak pernah menampilkan "maksimal".
                                            'keterangan' => $hasil['batas_menit'] > 0
                                                ? 'maksimal '.$hasil['batas_menit'].' menit'
                                                : 'tidak dibatasi',
                                            'nada' => 'waktu',
                                        ],
                                        [
                                            'ikon' => 'daftar-cek',
                                            'label' => 'Total Soal',
                                            'nilai' => (string) $hasil['jumlah_soal'],
                                            'keterangan' => 'soal',
                                            'nada' => 'soal',
                                        ],
                                    ];
                                @endphp

                                @foreach ($statistik as $kartu)
                                    <article data-reveal class="uiux-hasil__statistik uiux-hasil__statistik--{{ $kartu['nada'] }}">
                                        <span class="uiux-hasil__statistik-ikon" aria-hidden="true">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path($kartu['ikon']) }}" />
                                            </svg>
                                        </span>

                                        <p class="uiux-hasil__statistik-label">{{ $kartu['label'] }}</p>

                                        <p class="uiux-hasil__statistik-nilai">{{ $kartu['nilai'] }}</p>

                                        <p class="uiux-hasil__statistik-keterangan">{{ $kartu['keterangan'] }}</p>
                                    </article>
                                @endforeach
                            </div>

                            {{-- Soal yang dilewati tidak ikut dihitung sebagai
                                 salah, jadi angka "dari N soal" di kartu
                                 Jawaban Salah bisa saja tidak menyamai
                                 Jawaban Benar. Ditulis apa adanya supaya
                                 angkanya tidak terlihat salah. --}}
                            @if ($hasil['belum_dijawab'] > 0)
                                <p class="uiux-hasil__catatan">
                                    {{ $hasil['belum_dijawab'] }} soal belum dijawab, jadi tidak dihitung sebagai jawaban salah.
                                </p>
                            @endif

                            {{-- ==================== TOMBOL ==================== --}}
                            <div data-reveal class="uiux-hasil__aksi">
                                <a href="{{ route('user.dashboard') }}" class="uiux-hasil__tombol uiux-hasil__tombol--garis">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('rumah') }}" />
                                    </svg>

                                    Kembali ke Beranda
                                </a>

                                {{-- Rute ini sudah ada: /user/hasil/{pengerjaan},
                                     halaman yang sudah membawa rincian jawaban
                                     per soal. --}}
                                <a href="{{ $hasil['tautan_detail'] }}" class="uiux-hasil__tombol uiux-hasil__tombol--utama">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('mata') }}" />
                                    </svg>

                                    Lihat Hasil Detail
                                </a>
                            </div>

                            @if ($tautanSesi !== null)
                                <p class="uiux-hasil__catatan uiux-hasil__catatan--tengah">
                                    Pengerjaan ini bagian dari sesi live. Rekap nilai seluruh peserta ada di
                                    <a href="{{ $tautanSesi }}" class="font-semibold text-primary hover:text-primary-dark">halaman hasil sesi</a>.
                                </p>
                            @endif
                        </div>

                        {{-- ==================== DETAIL QUIZ ====================
                             Turun ke bawah kolom utama di tablet dan
                             mobile, jadi tidak pernah menyisipkan ruang
                             kosong yang membuat kartu nilai terasa sempit. --}}
                        <aside class="min-w-0 space-y-4">
                            <section data-reveal class="uiux-hasil__kartu">
                                <h2 class="uiux-hasil__sub-judul">
                                    <svg class="h-5 w-5 shrink-0 text-primary" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('dokumen') }}" />
                                    </svg>

                                    Detail Quiz
                                </h2>

                                <dl class="uiux-hasil__meta">
                                    <div class="uiux-hasil__meta-item">
                                        <dt>Nama Quiz</dt>
                                        <dd>{{ $hasil['judul'] }}</dd>
                                    </div>

                                    <div class="uiux-hasil__meta-item">
                                        <dt>Mata Pelajaran</dt>
                                        <dd>{{ $hasil['kategori']['nama'] }}</dd>
                                    </div>

                                    <div class="uiux-hasil__meta-item">
                                        <dt>Tanggal Pengerjaan</dt>
                                        <dd>{{ $hasil['tanggal_label'] ?? '-' }}</dd>
                                    </div>

                                    <div class="uiux-hasil__meta-item">
                                        <dt>Tingkat Kesulitan</dt>
                                        <dd>{{ $hasil['tingkat_kesulitan'] ?? 'Tidak ditentukan' }}</dd>
                                    </div>

                                    <div class="uiux-hasil__meta-item">
                                        <dt>Jumlah Soal</dt>
                                        <dd>{{ $hasil['jumlah_soal'] }} soal</dd>
                                    </div>

                                    <div class="uiux-hasil__meta-item">
                                        <dt>Batas Waktu</dt>
                                        <dd>
                                            {{ $hasil['batas_menit'] > 0 ? $hasil['batas_menit'].' menit' : 'Tidak dibatasi' }}
                                        </dd>
                                    </div>
                                </dl>

                                {{-- Penjelasan rumus, ditulis dari aturan yang
                                     benar-benar dipakai di server:
                                     (benar / jumlah soal) x 100. --}}
                                <p class="uiux-hasil__info">
                                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('lampu') }}" />
                                    </svg>

                                    <span>
                                        Nilai kamu dihitung berdasarkan jumlah jawaban benar dari total soal, dengan skala 0 - 100.

                                        @if ($hasil['contoh_rumus'] !== null)
                                            <span class="uiux-hasil__contoh">{{ $hasil['contoh_rumus'] }}</span>
                                        @endif
                                    </span>
                                </p>
                            </section>
                        </aside>
                    </div>
                </div>
            @endunless
        </div>
    </div>
@endsection
