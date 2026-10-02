{{--
    Baris tombol navigasi wizard, dipakai langkah 1 dan 3.

    Langkah 2 tidak memakai baris ini: keempat aksinya (Kembali, Batal,
    Draft, Lanjut ke Pengaturan) berkumpul di kartu aksi di bawah daftar
    soal, dan baris ini ikut disembunyikan selama langkah itu terbuka
    (resources/js/quiz-tambah.js).

    Tombol "Kembali" di langkah pertama disembunyikan, bukan dibuat
    nonaktif, supaya tidak ada tombol mati yang menyisakan ruang kosong
    di bawah kartu. Aksi memproses langkah sebelumnya ditangani
    resources/js/quiz-tambah.js lewat data-wizard-kembali.

    Prop $batal menentukan tujuan tombol Batal. Nilai bakanya adalah
    "Karya Saya", tempat pulang form milik pemilik; kedua form admin
    (admin.quiz-edit dan admin.konten-quiz) mengoper route daftarnya sendiri
    supaya tombolnya tidak melempar admin ke halaman yang bukan miliknya.

    Prop $admin mengganti tombol "Selesai & Simpan" milik form pemilik
    dengan dua tombol milik ruang kerja admin: "Simpan Draft" dan
    "Publish Sekarang". Keduanya baru muncul di langkah terakhir, dan
    keduanya sudah jadi keputusan akhir — bukan perpindahan tempat.
--}}

@props([
    // Tujuan tombol Batal. Null = form milik pemilik, yang pulang ke Karya
    // Saya. Kedua form admin mengoper route daftarnya sendiri.
    'batal' => null,

    /*
     * Form milik ruang kerja admin (admin.konten-quiz).
     *
     * Bedanya hanya isi baris tombol di langkah terakhir. Form admin tidak
     * memakai tombol "Selesai & Simpan": di situ admin memilih antara
     * menyimpan sebagai draft dan menerbitkan sekarang, jadi keduanya perlu
     * tampil berdampingan dan tombol simpan milik form pemilik tidak ada
     * gunanya.
     */
    'admin' => false,
])

{{--
    $admin memakai bentuk kartu: di langkah 1 dan 3 baris tombolnya diletakkan
    di dalam kartu yang sama dengan kartu aksi langkah 2 (.panel-aksi), jadi
    bentuknya tidak berubah-ubah saat admin berpindah langkah. Lihat
    .wizard-navigasi--karta di resources/css/app.css.
--}}
<nav @class(['wizard-navigasi', 'wizard-navigasi--karta' => $admin]) aria-label="Navigasi wizard">
    {{-- Kembali ke langkah sebelumnya; hanya muncul dari langkah kedua. --}}
    <button type="button" class="tombol-garis wizard-navigasi__kembali" data-wizard-kembali hidden>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>

        Kembali
    </button>

    <a href="{{ $batal ?? route('user.karya-saya', ['tab' => 'quiz']) }}" class="tombol-garis" data-wizard-batal>Batal</a>

    <span class="wizard-navigasi__spacer"></span>

    {{-- Lanjut: berpindah langkah, tidak pernah mengirim form. --}}
    <button type="button" class="tombol-utama wizard-navigasi__lanjut" data-wizard-lanjut>
        <span data-wizard-lanjut-teks>Lanjutkan</span>

        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
        </svg>
    </button>

    @if ($admin)
        {{--
            Dua tombol keputusan akhir milik form admin.

            Keduanya TIDAK disembunyikan di sini, unlike tombol "Selesai &
            Simpan" milik form pemilik di bawah. Alasannya: Publish Sekarang
            adalah tujuan akhir dari form ini, jadi ia tidak boleh hilang
            hanya karena JavaScript belum sempat berjalan — misalnya saat
            bundle asetnya masih versi lama. resources/js/quiz-tambah.js
            yang menyembunyikannya kembali selama belum di langkah terakhir,
            jadi bentuk finalnya tetap rapi.

            "Simpan Draft" memakai atribut yang sama dengan tombol Draft di
            langkah 2 (data-wizard-draft), jadi keduanya satu aksi yang sama:
            periksa semua langkah, lalu kirim form dengan aksi "draft". Yang
            membedakan hanya kapan tampilnya — tombol di baris navigasi hanya
            muncul di langkah terakhir, sementara tombol di langkah 2 ikut
            tersembunyi bersama panelnya.

            "Publish Sekarang" type="button" dan berada di luar <form>, jadi
            yang mengirim bukan form-nya melainkan fungsi yang dipasang wizard
            di window.kelasKitaKontenKirim. resources/js/konten-publish.js
            menahan tombol ini lebih dulu untuk membuka dialog konfirmasi,
            lalu wizard memeriksa semua langkah sebelum form benar-benar
            dikirim.

            Tanpa JavaScript keduanya tidak melakukan apa-apa. Isian form tetap
            bisa dikirim lewat Enter, dan terbit / batal terbit tetap tersedia
            dari daftar Konten Pembelajaran.
        --}}
        <button type="button" class="tombol-garis" data-wizard-draft>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>

            Simpan Draft
        </button>

        <button type="button" class="tombol-utama" data-wizard-terbit
            data-konten-publish="publish" data-konten-kirim>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>

            Publish Sekarang
        </button>
    @else
        {{--
            Selesai: hanya di langkah ketiga.

            Sengaja type="button", bukan submit, supaya tombolnya bisa menahan
            pengiriman lebih dulu untuk memeriksa isian per langkah. Kalau
            semua isian lolos barulah form dikirim (lihat
            resources/js/quiz-tambah.js).
        --}}
        <button type="button" class="tombol-utama wizard-navigasi__simpan" data-wizard-simpan hidden>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>

            <span data-wizard-simpan-teks>Selesai &amp; Simpan</span>
        </button>
    @endif
</nav>
