{{--
    Isi tab "Preview" pada kartu Daftar Bab: pratinjau tampilan siswa.

    Komponen ini sengaja memakai komponen halaman detail yang sama
    (x-materi.detail-kepala dan kelas kartu yang dipakai
    x-materi.detail-seksi), supaya yang terlihat di sini persis seperti
    halaman yang nanti dibaca siswa. Bedanya hanya sumber isinya: di sini
    semuanya berasal dari form dan diisi ulang oleh
    resources/js/materi-tambah.js setiap kali bab, judul, atau kategori
    berubah.

    Dua bagian halaman detail yang tidak ikut ditiru:
      - Daftar Isi: saat materi baru disusun, daftar babnya masih ikut
        berganti setiap kali bab ditambah atau dihapus, jadi belum bisa
        dipakai sebagai navigasi. Yang menggantikannya adalah tombol
        Sebelumnya / Selanjutnya di bawah.
      - Tombol Simpan: materi yang sedang disusun belum punya slug, jadi
        belum bisa disimpan.

    Data placeholder untuk kepala pratinjau dirakit di dalam komponen ini,
    jadi pemanggil cukup menulis <x-materi.preview :kategori="$kategori" :materi="$materi" />.
--}}
@props([
    // Kategori dan materi yang sedang disusun, diteruskan dari form.
    // Dipakai untuk merakit kepala pratinjau supaya dimulai dari nilai
    // yang sama dengan isian form.
    'kategori' => [],
    'materi' => null,
])

@php
    /*
     * Kategori dan tingkat kesulitan yang terpilih di form. Pratinjau
     * dimulai dari nilai yang sama supaya tidak melompat begitu tab
     * Preview dibuka untuk pertama kali.
     */
    $pelajaranTerpilih = collect($kategori)
        ->first(fn ($item) => (int) $item->id === (int) old('pelajaran_id', $materi?->pelajaran_id));
    $gayaKategori = \App\Models\Pelajaran::warna(
        $pelajaranTerpilih?->slug ?? '',
        $pelajaranTerpilih?->nama ?? 'Pilih kategori'
    );

    /*
     * Halaman detail menampilkan waktu baca sebagai angka menit ("10 menit
     * baca"), sedangkan isian form bebasnya teks ("10 menit", "1 jam").
     * Angkanya yang dipakai supaya pratinjau dan halaman detail kalimatnya
     * sama persis.
     */
    $estimasi = (string) old('estimasi_waktu', '10 menit');
    $waktuBaca = preg_match('/\d+/', $estimasi, $angka) ? (int) $angka[0] : 10;

    $pengguna = auth()->user();

    $detailPratinjau = [
        'judul' => old('nama', $materi?->nama) ?: 'Judul materi belum diisi',
        'slug' => '',
        // Form tidak punya isian deskripsi, jadi sama seperti halaman detail
        // untuk materi yang memang tidak berdeskripsi, bagian ini tidak
        // dirender.
        'deskripsi' => null,
        'thumbnail' => $materi?->thumbnail ? \App\Support\BerkasMateri::url($materi->thumbnail) : null,
        'tingkat_kesulitan' => old('tingkat_kesulitan', $materi?->tingkat_kesulitan ?? 'Mudah'),
        'waktu_baca' => $waktuBaca,
        'tanggal' => \App\Support\DetailMateri::tanggal(now()),
        'jumlah_dilihat' => 0,
        'dilihat' => '0',
        'tersimpan' => false,
        'kategori' => $gayaKategori,
        'pembuat' => $pengguna ? [
            'nama' => $pengguna->nama,
            'inisial' => $pengguna->inisial(),
            'warna' => $pengguna->warnaAvatar()['warna'],
            'warna_gelap' => $pengguna->warnaAvatar()['warna_gelap'],
        ] : null,
    ];
@endphp

<div {{ $attributes->class(['pratinjau-kotak']) }}>
    {{-- Progress bab + posisi bab yang sedang aktif --}}
    <div class="mb-3 flex items-center gap-3">
        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-ungu-bg" role="presentation">
            <div data-preview-bar class="h-full rounded-full bg-ungu transition-[width] duration-300"
                style="width: 100%"></div>
        </div>

        <span data-preview-indeks
            class="shrink-0 rounded-full bg-ungu-bg px-2.5 py-1 text-xs font-extrabold tabular-nums text-ungu">
            1 / 1
        </span>
    </div>

    {{-- Kepala: komponen yang sama dengan halaman detail materi. --}}
    <x-materi.detail-kepala :detail="$detailPratinjau" pratinjau />

    {{--
        Isi bab aktif. Strukturnya meniru x-materi.detail-seksi: kartu
        yang sama, judul bernomor yang sama, dan kelas .isi-materi yang
        sama untuk tipografinya. Bedanya isi bab belum dipecah jadi blok
        seperti di halaman detail, jadi masih ditampilkan apa adanya
        seperti yang tertulis di editor.
    --}}
    <section class="kartu-detail materi-seksi mt-4 p-5 sm:p-6 lg:p-7">
        <h2 class="materi-seksi__judul" data-preview-bab>1. Bab Baru</h2>

        <div class="isi-materi mt-4" data-preview-isi></div>
    </section>

    {{-- Navigasi preview: pengganti Daftar Isi yang belum bisa dipakai. --}}
    <div class="mt-3 flex items-center justify-between gap-2">
        <button type="button" data-preview-prev
            class="tombol-garis px-3 py-1.5 text-xs disabled:cursor-not-allowed disabled:opacity-40">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>

            Sebelumnya
        </button>

        <span data-preview-posisi class="text-[11px] font-bold text-muted">Bab 1 dari 1</span>

        <button type="button" data-preview-next
            class="tombol-garis px-3 py-1.5 text-xs disabled:cursor-not-allowed disabled:opacity-40">
            Selanjutnya

            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </button>
    </div>
</div>
