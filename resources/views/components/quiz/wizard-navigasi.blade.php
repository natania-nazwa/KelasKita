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
--}}

<nav class="wizard-navigasi" aria-label="Navigasi wizard">
    {{-- Kembali ke langkah sebelumnya; hanya muncul dari langkah kedua. --}}
    <button type="button" class="tombol-garis wizard-navigasi__kembali" data-wizard-kembali hidden>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>

        Kembali
    </button>

    <a href="{{ route('user.karya-saya', ['tab' => 'quiz']) }}" class="tombol-garis" data-wizard-batal>Batal</a>

    <span class="wizard-navigasi__spacer"></span>

    {{-- Lanjut: berpindah langkah, tidak pernah mengirim form. --}}
    <button type="button" class="tombol-utama wizard-navigasi__lanjut" data-wizard-lanjut>
        <span data-wizard-lanjut-teks>Lanjutkan</span>

        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
        </svg>
    </button>

    {{--
        Selesai: hanya di langkah ketiga.

        Sengaja         type="button", bukan submit, supaya tombolnya bisa menahan
        pengiriman lebih dulu untuk memeriksa isian per langkah. Kalau
        semua isian lolos barulah form dikirim (lihat
        resources/js/quiz-tambah.js).
    --}}
    <button type="button" class="tombol-utama wizard-navigasi__simpan" data-wizard-simpan hidden>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>

        <span data-wizard-simpan-teks>Selesai &amp; Simpan</span>
    </button>
</nav>
