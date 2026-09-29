@extends('layouts.app')

@section('title', ($tanpaSoal ? $quiz->judul : 'Soal '.$nomor.' dari '.$jumlahSoal.' | '.$quiz->judul).' | KelasKita')

@section('content')
    @php
        /*
         * Halaman mengerjakan soal memakai satu kolom selebar max-w-4xl
         * (56rem). Lebarnya sedikit lebih longgar dari batas baca yang
         * dipakai halaman lain, karena isiannya bukan paragraf panjang
         * melainkan pilihan jawaban dan kolom isian pendek: yang bikin
         * virtuoso adalah ruang kosong di kiri dan kanan, bukan panjang
         * teks soalnya.
         *
         * Elemen terluar sekaligus jadi kolom flex (lihat .soal-kanvas di
         * app.css): kepalanya tetap setinggi isinya, lalu kartu soal yang
         * menyerap seluruh sisa tinggi layar, sehingga kartu soal dan
         * kakinya menempel ke tepi bawah layar dan tidak ada ruang kosong
         * menggantung di bawahnya.
         *
         * URL menyebut quiz-nya sendiri lewat slug judul, lalu nomor soalnya:
         * /user/quiz/{slug}/soal/{nomor}, misalnya /user/quiz/seputar-teknologi/soal/1.
         * Id sesi tidak ikut di URL; ia dikembalikan lewat field "sesi" di
         * setiap tautan dan form, jadi mengetik "/user/quiz/seputar-teknologi/soal/2"
         * tanpa tautan tidak akan tahu sesi mana yang dimaksud.
         */
        $idSesi = $sesi->getKey();
        $tautanSoal = fn (int $nomor) => route('user.judulsoal.soal', [$quiz->slug, $nomor]).'?sesi='.$idSesi;
        $kategori = $quiz->pelajaran?->nama ?? 'Umum';
        $soalTerakhir = $nomor === $jumlahSoal;

        /*
         * Nomor kotak untuk dialog daftar soal. Dicek dulu supaya quiz
         * tanpa soal (jumlahSoal = 0) tidak menghasilkan larik terbalik:
         * range(1, 0) di PHP mengembalikan [1, 0], bukan larik kosong.
         */
        $nomorSoal = $jumlahSoal > 0 ? range(1, $jumlahSoal) : [];

        // Soal ini sendiri sudah ditandai ragu atau belum. Dipisah dari
        // $nomorRagu karena yang ini cuma satu soal, sedangkan yang itu
        // larik seluruh soal pada dialog daftar soal.
        $ragu = in_array($nomor, $nomorRagu, true);

        /*
         * Bentuk isian mengikuti tipe soal. Dua tipe teks menaruh isiannya
         * di "jawaban_teks", empat tipe berdaftar di "jawaban"; nama field
         * ikut dikirim ke view supaya pesan kesalahan dari server dan
         * elemen lantainya menunjuk isian yang sama.
         */
        $tipe = $soal?->tipe() ?? \App\Models\Soal::TIPE_PILIHAN_GANDA;
        $kunciJawaban = $soal?->tipeTeks() === true ? 'jawaban_teks' : 'jawaban';
        $pesanBelumDijawab = 'Silakan pilih atau isi jawaban terlebih dahulu.';

        $pilihan = $soal?->pilihan() ?? [];
        $terpilih = $soal?->tipeBanyakBenar() === true
            ? ($jawaban?->hurufDipilih() ?? old('jawaban', []))
            : (string) old('jawaban', $jawaban?->jawaban_dipilih);
        $teksJawaban = (string) old('jawaban_teks', $jawaban?->jawaban_teks);

        /*
         * Satu kalimat singkat di atas isian supaya peserta tahu persis
         * apa yang boleh dilakukan pada tipe soal ini. Untuk tipe berdaftar
         * bedanya mencolok: "tepat satu" dan "satu atau lebih" punya
         * perlakuan berbeda, dan itu tidak bisa ditebak dari tampilannya.
         */
        $petunjuk = match ($tipe) {
            \App\Models\Soal::TIPE_PILIHAN_BANYAK => 'Pilih satu atau lebih jawaban yang benar.',
            \App\Models\Soal::TIPE_BENAR_SALAH => 'Pilih Benar atau Salah.',
            \App\Models\Soal::TIPE_DROPDOWN => 'Pilih satu jawaban dari daftar.',
            \App\Models\Soal::TIPE_JAWABAN_SINGKAT => 'Tulis jawaban singkatmu di bawah.',
            \App\Models\Soal::TIPE_PARAGRAF => 'Tulis jawabanmu di bawah. Jawaban ini dinilai setelah kamu mengumpulkan semua soal.',
            default => 'Pilih tepat satu jawaban.',
        };
    @endphp

    {{--
        data-soal-halaman: dipakai timer untuk mengunci seluruh isi halaman
        begitu waktu habis. Dialog "Waktu Anda Habis" sengaja diletakkan
        DI LUAR elemen ini, karena elemen yang dikunci tidak boleh memuat
        tombol yang harus tetap bisa diklik.
    --}}
    <div
        class="soal-kanvas kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-4 sm:p-6 lg:-m-10 lg:min-h-[100dvh] lg:p-10"
        data-soal
        data-soal-halaman
        data-soal-total="{{ $jumlahSoal }}"
        data-soal-dijawab="{{ $jumlahDijawab }}"
    >
        <div class="soal-kanvas__isi mx-auto w-full max-w-4xl">

            @if ($tanpaSoal)
                {{--
                    Quiz ini belum punya soal aktif. Bukan 404: sesi dan
                    quiz-nya ada, hanya pertanyaannya yang belum diisi, jadi
                    peserta diberi tahu apa yang terjadi dan ke mana harus
                    kembali, bukan diberi halaman kosong.
                --}}
                <section class="lobi-kartu" data-reveal="zoom">
                    <div class="lobi-kartu__badan py-12 text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-ungu-bg text-primary"
                            aria-hidden="true">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('dokumen') }}" />
                            </svg>
                        </span>

                        <h1 class="mt-5 text-lg font-extrabold tracking-tight text-dark">
                            Quiz belum memiliki soal
                        </h1>

                        <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-muted">
                            Quiz ini belum dapat dikerjakan karena belum memiliki pertanyaan.
                        </p>

                        <a href="{{ route('user.quiz.detail', $quiz) }}" class="tombol-garis mt-6">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kiri') }}" />
                            </svg>

                            Kembali
                        </a>
                    </div>
                </section>
            @else
                {{--
                    Kepala quiz: judul di kiri, progress di tengah, tombol
                    daftar soal lalu timer di kanan. Di layar sempit baris
                    pertama turun ke bawah judul supaya tidak saling
                    berdesakan dan tidak memunculkan scroll horizontal.
                --}}
                <section class="soal-kepala" data-reveal="zoom">
                    <div class="soal-kepala__kiri">
                        <span class="soal-kepala__ikon" aria-hidden="true">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('buku') }}" />
                            </svg>
                        </span>

                        <div class="min-w-0">
                            <p class="soal-kepala__judul">{{ $quiz->judul }}</p>

                            <p class="soal-kepala__meta">
                                {{ $kategori }} &middot; {{ $jumlahSoal }} soal
                            </p>
                        </div>
                    </div>

                    <div class="soal-kepala__tengah">
                        {{--
                            Teks "Soal 3 dari 8" tidak lagi ditulis di kepala.
                            Angka yang sama sudah ada di dalam dialog daftar
                            soal, jadi mengulangnya di sini cuma membuat
                            kepala ramai tanpa menambah informasi.

                            Yang tersisa progress bar. Teks posisinya tetap
                            ada, tapi disembunyikan dengan sr-only dan
                            ditaruh di luar progress bar: di dalam
                            progressbar semua isinya diperlakukan sebagai
                            presentasional, jadi screen reader tidak akan
                            membacanya.
                        --}}
                        <span class="sr-only">Soal {{ $nomor }} dari {{ $jumlahSoal }}</span>

                        <div class="soal-kepala__progres" role="progressbar"
                            aria-valuemin="1" aria-valuemax="{{ $jumlahSoal }}" aria-valuenow="{{ $nomor }}"
                            aria-label="Posisi soal">
                            <i style="width: {{ round($nomor / $jumlahSoal * 100) }}%"></i>
                        </div>
                    </div>

                    {{--
                        Dua kendali di kanan, urutannya penting: tombol daftar
                        soal lebih dulu, timer sesudahnya. Tombolnya ada di
                        semua quiz, timer hanya kalau quiz punya batas waktu
                        (quiz.durasi dalam menit) — jadi pembungkus kanannya
                        tidak ikut hilang bersama timer.
                    --}}
                    <div class="soal-kepala__kanan">
                        <button type="button" class="soal-nav__buka" data-soal-nav-buka
                            aria-haspopup="dialog" aria-controls="dialog-soal-nav">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kotak') }}" />
                            </svg>

                            <span class="soal-nav__buka__angka">{{ $jumlahDijawab }}</span>
                            <span class="soal-nav__buka__total">/ {{ $jumlahSoal }}</span>

                            <span class="sr-only">Buka daftar soal. Sudah dijawab {{ $jumlahDijawab }} dari {{ $jumlahSoal }}.</span>
                        </button>

                        {{--
                            Sisa waktunya dihitung server dari saat
                            pengerjaan dimulai, bukan dari saat halaman ini
                            dibuka, jadi refresh tidak mengulang waktu dari
                            awal.

                            Tiga ambang warna (aman, kurang dari
                            SesiKerjakanController::WAKTU_SEDIKIT, dan kurang dari
                            WAKTU_MENDEKUTI) dikirim lewat data-* supaya
                            JavaScript memakai angka yang sama dengan yang dipakai
                            server saat merender warna awalnya.
                        --}}
                        @if ($sisaDetik !== null)
                            <span class="soal-kepala__jam"
                                data-soal-timer
                                data-sisa="{{ $sisaDetik }}"
                                data-sedikit="{{ $waktuSedikit }}"
                                data-mendekuti="{{ $waktuMendekuti }}">

                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
                                </svg>

                                <span data-soal-timer-teks>{{ \App\Support\Angka::waktu($sisaDetik) }}</span>
                            </span>
                        @endif
                    </div>
                </section>

                {{-- Kartu soal. --}}
                <section class="lobi-kartu soal-kartu" data-reveal="zoom">
                    <div class="lobi-kartu__badan">

                        <div class="flex items-center justify-between gap-3">
                            <p class="soal-kartu__label">Soal {{ $nomor }}</p>

                            @if ($adalahHost)
                                <span class="karya-status karya-status--menunggu">Host</span>
                            @endif
                        </div>

                        {{-- Pertanyaan: teks paling menonjol di kartu. --}}
                        <h1 class="soal-kartu__pertanyaan">{{ $soal->pertanyaan }}</h1>

                        <p class="soal-kartu__status">
                            {{ $jumlahDijawab }} dari {{ $jumlahSoal }} soal sudah dijawab.
                        </p>

                        {{--
                            Satu form untuk menyimpan jawaban dan berpindah
                            soal. Isiannya mengikuti tipe soalnya: empat tipe
                            berdaftar mengirim huruf di "jawaban", dua tipe
                            teks mengirim isiannya di "jawaban_teks".

                            Di soal terakhir, form ini sekaligus berarti
                            "selesai", jadi ia memakai dialog konfirmasi yang
                            sama dengan aksi lain di sesi. Tanpa JavaScript
                            form tetap dikirim biasa.

                            Form ini sengaja berhenti sebelum kaki soal.
                            Tombol "Ragu" di antara "Sebelumnya" dan
                            "Selanjutnya" adalah form-nya sendiri, dan HTML
                            tidak boleh punya form di dalam form. Tombol
                            "Selanjutnya" tetap bisa mengirim form ini dari
                            luar lewat atribut form="soal-jawab" di bawahnya,
                            jadi tidak ada yang berubah dari sisi perilakunya.
                        --}}
                        <form method="POST" action="{{ route('user.judulsoal.jawab', [$quiz->slug, $nomor]) }}"
                            id="soal-jawab"
                            class="mt-5"
                            data-soal-form
                            data-soal-tipe="{{ $tipe }}"
                            @if ($soalTerakhir)
                                data-lobi-konfirmasi-judul="Selesaikan Quiz?"
                                data-lobi-konfirmasi-pesan="Pastikan semua jawaban sudah benar sebelum mengirim quiz."
                                data-lobi-konfirmasi-tombol="Selesaikan Quiz"
                            @endif>
                            @csrf

                            {{-- Id sesi dikembalikan lewat sini, bukan lewat URL. --}}
                            <input type="hidden" name="sesi" value="{{ $idSesi }}">

                            @include('user.partials.quiz.jawaban', [
                                'soal' => $soal,
                                'tipe' => $tipe,
                                'pilihan' => $pilihan,
                                'terpilih' => $terpilih,
                                'teksJawaban' => $teksJawaban,
                                'petunjuk' => $petunjuk,
                            ])

                            {{--
                                Satu elemen untuk pesan dari server maupun
                                dari peramban, jadi keduanya tidak menulis
                                pesan di tempat berbeda dan tidak pernah
                                tampil berdua. Kuncinya ikut ikut, karena
                                tipe teks menaruh pesannya di "jawaban_teks".

                                Teks awalnya sudah diisi di markup supaya pesan
                                dari server punya tempat tanpa perlu
                                JavaScript.
                            --}}
                            <p role="alert" class="soal-kartu__galat"
                                data-soal-galat
                                @if (! $errors->has($kunciJawaban)) hidden @endif>
                                {{ $errors->first($kunciJawaban) ?: $pesanBelumDijawab }}
                            </p>
                        </form>

                        {{--
                            Navigasi antar soal, plus tanda "ragu" di
                            tengahnya.

                            Ketiganya satu baris di desktop dan tiga baris di
                            layar sempit (urutannya dibalik: "Selanjutnya"
                            paling atas karena itu aksi utama). "Sebelumnya"
                            dan "Ragu" tidak ikut mengirim form jawaban: yang
                            pertama cuma tautan, yang kedua form-nya sendiri.
                        --}}
                        <div class="soal-kaki">
                            @if ($nomor > 1)
                                <a href="{{ $tautanSoal($nomor - 1) }}" class="tombol-garis justify-center">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kiri') }}" />
                                    </svg>

                                    Sebelumnya
                                </a>
                            @else
                                {{--
                                    Soal pertama tidak punya sebelumnya.
                                    Tombolnya tetap ada supaya tinggi
                                    kartu tidak berubah-ubah saat
                                    berpindah soal, hanya dibuat tidak
                                    bisa ditekan dan dikeluarkan dari
                                    urutan baca layar pembaca.
                                --}}
                                <span class="tombol-garis soal-kaki__nonaktif justify-center" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" tabindex="-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kiri') }}" />
                                    </svg>

                                    Sebelumnya
                                </span>
                            @endif

                            {{--
                                Tanda "ragu": form sendiri, tidak pernah
                                ikut mengirim isian jawaban.

                                Satu aksi untuk dua arah — klik pertama
                                menandai, klik berikutnya melepas — jadi
                                peserta tidak perlu mengingat apakah
                                tandanya sudah hidup atau belum. Kotak
                                kecil di dalam tombollah yang
                                menunjukkan itu lewat centangnya.

                                Setelah berubah, halaman dikembalikan ke
                                soal yang sama, jadi posisi peserta tidak
                                bergeser hanya karena menandai sesuatu.
                            --}}
                            <form method="POST" action="{{ route('user.judulsoal.ragu', [$quiz->slug, $nomor]) }}"
                                class="soal-ragu {{ $ragu ? 'soal-ragu--ada' : '' }}">
                                @csrf

                                <input type="hidden" name="sesi" value="{{ $idSesi }}">

                                <button type="submit" class="soal-ragu__tombol"
                                    aria-pressed="{{ $ragu ? 'true' : 'false' }}">
                                    <span class="soal-ragu__kotak" aria-hidden="true">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('tanda-centang') }}" />
                                        </svg>
                                    </span>

                                    Ragu
                                </button>
                            </form>

                            {{--
                                "Selanjutnya" hidup di luar form jawaban
                                (lihat catatan pada form di atas), tapi
                                tetap mengirimnya lewat atribut
                                form="soal-jawab". Label di soal terakhir
                                memang berbeda karena yang tombolnya
                                lakukan juga berbeda: menutup pengerjaan,
                                bukan berpindah soal.
                            --}}
                            <button type="submit" form="soal-jawab" class="tombol-utama justify-center" data-soal-lanjut>
                                {{ $soalTerakhir ? 'Selesai & Lihat Hasil' : 'Selanjutnya' }}

                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kanan') }}" />
                                </svg>
                            </button>
                        </div>

                        {{--
                            Berhenti di tengah. Form ini dipisah dari form
                            jawaban karena ia tidak menyimpan jawaban apa pun,
                            dan karena tombolnya tidak boleh ikut terkirim
                            saat validasi jawaban gagal.

                            Tanpa JavaScript tombolnya tetap mengirim form
                            seperti biasa, jadi berhenti di tengah tidak
                            pernah menjadi jalan buntu.
                        --}}
                        @if (! $soalTerakhir && $jumlahDijawab > 0)
                            <form method="POST" action="{{ route('user.sesi.selesai', $sesi->getKey()) }}"
                                class="mt-5 text-center"
                                data-lobi-konfirmasi-judul="Selesaikan Quiz sekarang?"
                                data-lobi-konfirmasi-pesan="Jawaban yang sudah tersimpan tetap dihitung, tapi soal yang belum dijawab tidak ikut dinilai."
                                data-lobi-konfirmasi-tombol="Ya, Selesaikan">
                                @csrf

                                <button type="submit" class="soal-keluar">
                                    Selesaikan sekarang dan lihat hasil
                                </button>
                            </form>
                        @endif
                    </div>
                </section>

                {{--
                    Form terpisah untuk menutup pengerjaan ketika waktu
                    habis. Sengaja disembunyikan dan tidak punya tombol:
                    pengirimnya adalah timer di resources/js/quiz-kerjakan.js
                    yang memanggil .submit() setelah peserta menekan tombol
                    pada dialog "Waktu Anda Habis".

                    Form memakai aksi selesai yang sama dengan tombol di atas,
                    jadi waktu habis tidak menghasilkan jalur penilaian yang
                    berbeda dari selesai secara manual. Kalau JavaScript tidak
                    berjalan, form ini tetap tidak terlihat dan tidak ada
                    yang salah.
                --}}
                @if ($sisaDetik !== null)
                    <form method="POST" action="{{ route('user.sesi.selesai', $sesi->getKey()) }}" data-soal-waktu-habis hidden>
                        @csrf
                    </form>
                @endif
            @endif
        </div>
    </div>

    {{-- Dialog konfirmasi "Selesaikan Quiz?" dipakai bersama dengan aksi lobby
         dan hasil lewat atribut data-* pada form pemanggilnya. --}}
    <x-sesi.konfirmasi judul="Selesaikan Quiz?"
        pesan="Pastikan semua jawaban sudah benar sebelum mengirim quiz."
        tombol="Selesaikan Quiz" />

    {{--
        Dialog daftar soal.

        Tombolnya ada di kepala halaman, di sebelah kiri timer. Isinya dua
        bagian dari atas ke bawah: dua kartu ringkasan (total soal dan yang
        sudah dijawab), lalu kotak nomor 1..N yang jadi navigator.

        Warna kotak dibaca sebagai satu bahasa, bukan dua: ungu pekat berarti
        sudah dijawab, putih berarti belum. Karena itu soal yang sedang
        dibuka tidak memakai warna untuk ditandai, tapi cincin di sekeliling
        kotaknya — kalau ikut ungu, soal yang sedang dibuka tapi belum
        dijawab akan terlihat sama dengan yang sudah dijawab.

        Setiap kotak adalah tautan biasa ke soal itu, jadi daftar soal tetap
        bisa dipakai tanpa JavaScript.

        Dialog ini memakai .dialog-bab yang sama dengan dialog hapus bab dan
        dialog konfirmasi sesi, jadi tidak ada gaya dialog baru di app.css.
        Buka/tutunya dikerjakan initNavigasi() di resources/js/quiz-kerjakan.js.

        Sengaja diletakkan DI LUAR elemen ber-data-soal-halaman: begitu waktu
        habis, elemen itu di-lock jadi tidak bisa diklik, dan dialog daftar
        soal ikut tidak bisa dibuka. Yang masih boleh diklik setelah waktu
        habis hanya tombol "Lihat Hasil" pada dialog waktu habis.
    --}}
    @unless ($tanpaSoal)
        <div class="dialog-bab" id="dialog-soal-nav" data-soal-nav-dialog role="dialog" aria-modal="true"
            aria-labelledby="judul-dialog-nav" aria-describedby="pesan-dialog-nav">

            <div class="w-full max-w-md rounded-2xl border border-ungu-line bg-white p-5 shadow-[0_30px_60px_-30px_rgba(49,46,129,0.8)]">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="judul-dialog-nav" class="text-base font-extrabold text-dark">
                            Daftar Soal
                        </h2>

                        <p id="pesan-dialog-nav" class="mt-1 text-sm leading-relaxed text-muted">
                            Ketuk nomor soal untuk langsung pindah ke soal itu.
                        </p>
                    </div>

                    <button type="button" class="soal-nav__tutup" data-soal-nav-tutup
                        aria-label="Tutup daftar soal">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('silang-polos') }}" />
                        </svg>
                    </button>
                </div>

                {{--
                    Dua angka yang paling sering dicari peserta sebelum pindah
                    soal: berapa soal yang harus dikerjakan, dan berapa yang
                    sudah selesai. Angka keduanya diambil dari sumber yang
                    sama dengan isi daftar soal di bawahnya, jadi tidak
                    mungkin berbeda.
                --}}
                <div class="soal-nav__ringkasan">
                    <div class="soal-nav__stat">
                        <span class="soal-nav__stat__angka">{{ $jumlahSoal }}</span>
                        <span class="soal-nav__stat__label">Total soal</span>
                    </div>

                    <div class="soal-nav__stat soal-nav__stat--terjawab">
                        <span class="soal-nav__stat__angka">{{ $jumlahDijawab }}</span>
                        <span class="soal-nav__stat__label">Sudah dijawab</span>
                    </div>
                </div>

                {{--
                    Satu kotak per soal, urut dari 1. Kotak untuk soal yang
                    belum dibuka sekali pun tetap ikut dicetak: kotak
                    putihnya yang mengingatkan peserta bahwa soal itu masih
                    ada.

                    Urutan modem warnanya penting, dan ditulis urut di sini
                    supaya terbaca: soal yang ditandai ragu didahulukan
                    lebih dulu, baru soal yang sudah dijawab, lalu soal
                    yang sedang dibuka. Urut ini yang membuat "kuning"
                    berarti "tinjau lagi soal ini" walaupun soal itu sudah
                    dijawab — kalau dibalik, kuning akan hilang tepat pada
                    soal yang paling perlu ditinjau.
                --}}
                <div class="soal-nav">
                    <div class="soal-nav__daftar">
                        @foreach ($nomorSoal as $nomorKotak)
                            @php($terjawab = in_array($nomorKotak, $nomorTerjawab, true))
                            @php($raguKotak = in_array($nomorKotak, $nomorRagu, true))

                            {{--
                                Satu kelas dan satu keterangan, ditulis
                                sebagai ifelse berurutan supaya tidak ada
                                keadaan yang bisa terpotong oleh yang
                                lain. Bentuknya selalu sama: ragu, kalau
                                tidak terjawab, kalau tidak belum.
                            --}}
                            @php($kelasKotak = $raguKotak
                                ? 'soal-nav__tombol--ragu'
                                : ($terjawab ? 'soal-nav__tombol--terjawab' : ''))

                            @php($keadaanKotak = $raguKotak
                                ? 'ragu, perlu ditinjau lagi'
                                : ($terjawab ? 'sudah dijawab' : 'belum dijawab'))

                            <a href="{{ $tautanSoal($nomorKotak) }}"
                                class="soal-nav__tombol {{ $kelasKotak }} {{ $nomorKotak === $nomor ? 'soal-nav__tombol--kini' : '' }}"
                                @if ($nomorKotak === $nomor) aria-current="true" @endif
                                aria-label="Soal {{ $nomorKotak }}, {{ $keadaanKotak }}">{{ $nomorKotak }}</a>
                        @endforeach
                    </div>
                </div>

                {{--
                    Legenda "Sudah dijawab / Belum dijawab" tidak lagi
                    ditulis. Kotak yang sudah dijawab sudah ungu pekat dan
                    yang belum putih, jadi perbedaan sebesar itu langsung
                    terbaca tanpa perlu dijelaskan; yang tersisa cuma dua
                    baris teks yang tidak menambah informasi apa pun.
                --}}
                <button type="button" class="tombol-garis mt-4 w-full justify-center" data-soal-nav-tutup>
                    Tutup
                </button>
            </div>
        </div>
    @endunless

    {{--
        Waktu habis.

        Tanpa dialog ini, peserta langsung melompat dari soal ke halaman
        hasil begitu hitungan mencapai 00:00, tanpa tahu kenapa soalnya
        tidak bisa lagi dijawab.     Dialog ini menjelaskan kenapa soalnya tidak bisa lagi dijawab, dan
        karena isinya sudah dikunci di JavaScript, satu-satunya jalan keluar
        dari sini adalah tombol "Lihat Hasil".

        Sengaja tidak memakai x-sesi.konfirmasi: dialog itu punya tombol
        Batal, bisa ditutup dengan Escape, dan bisa ditutup dengan klik di
        luar kotak. Ketiganya tidak boleh ada di sini, karena membatalkan
        dialog sama dengan membiarkan peserta menjawab di luar waktunya.

        Hanya dirender kalau quiz punya batas waktu (sisaDetik tidak null),
        jadi quiz tanpa durasi tidak pernah punya dialog yang tidak akan
        pernah muncul.
    --}}
    @if ($sisaDetik !== null)
        <div class="dialog-bab" data-soal-dialog-habis role="dialog" aria-modal="true"
            aria-labelledby="judul-dialog-habis" aria-describedby="pesan-dialog-habis">

            <div class="w-full max-w-sm rounded-2xl border border-ungu-line bg-white p-5 text-center shadow-[0_30px_60px_-30px_rgba(49,46,129,0.8)]">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#fffbeb] text-[#d97706]"
                    aria-hidden="true">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
                    </svg>
                </span>

                <h2 id="judul-dialog-habis" class="mt-4 text-base font-extrabold text-dark">
                    Waktu Anda Habis
                </h2>

                <p id="pesan-dialog-habis" class="mt-1.5 text-sm leading-relaxed text-muted">
                    Waktu pengerjaan quiz ini sudah habis, jadi soal tidak bisa lagi dijawab.
                    Jawaban yang sudah kamu kirim tetap dihitung. Lihat hasil sekarang.
                </p>

                <button type="button" data-soal-dialog-habis-tombol
                    class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('mata') }}" />
                    </svg>

                    Lihat Hasil
                </button>
            </div>
        </div>
    @endif
@endsection