{{--
    Isi tab "Preview" pada kartu Daftar Bab: pratinjau tampilan pembaca.

    Pratinjau ini bukan tiruan halaman detail. Kepala pratinjau memakai
    komponen yang sama dengan halaman detail (x-materi.detail-kepala), dan
    badannya diambil dari server lewat endpoint pratinjau yang merakit model
    Materi lalu memanggil pemecah yang sama dengan halaman detail — jadi Daftar
    Isi, kartu seksi, blok kode yang diwarnai, dan navigasi antar bab di sini
    benar-benar keluaran komponen yang sama, bukan salinan yang bisa
    menyimpang begitu salah satu sisi berubah.

    Badannya tidak bisa dirakit di JavaScript: App\Support\IsiMateri dan
    App\Support\SorotKode bekerja di PHP. Menyalin aturannya ke JavaScript
    hanya akan menghasilkan versi kedua yang pasti menyimpang. Yang tetap
    dikerjakan di sisi klien cuma bagian yang benar-benar milik form: judul,
    kategori, tingkat kesulitan, dan thumbnail — semuanya masih berupa berkas
    di browser dan belum pernah menyentuh server.

    Endpoint-nya mengembalikan fragment (Daftar Isi + kartu seksi), bukan
    halaman utuh, dan ukurannya kecil: pemanggilnya cuma menukar isi satu
    wadah di dalam form.

    Dua kontrol milik pembaca disembunyikan di dalam pratinjau lewat
    .pratinjau-kotak di app.css: tombol salin kode dan tombol lipatkan Daftar
    Isi. Keduanya bergantung pada penyimpan atau pengukuran yang hanya ada di
    halaman detail, dan di sini tidak ada yang bisa dilayani — lebih baik tidak
    tampil daripada tampil sebagai tombol yang kelihatan bisa diklik tapi mati.

    Data placeholder untuk kepala pratinjau dirakit di dalam komponen ini,
    jadi pemanggil cukup menulis <x-materi.preview :kategori="$kategori" :materi="$materi" />.
--}}
@props([
    // Kategori dan materi yang sedang disusun, diteruskan dari form.
    // Dipakai untuk merakit kepala pratinjau supaya dimulai dari nilai
    // yang sama dengan isian form.
    'kategori' => [],
    'materi' => null,

    // Endpoint yang merender badan pratinjau. Wajib diisi oleh pemanggil:
    // form admin dan form pemilik menunjuki route-nya masing-masing, jadi
    // komponen ini tidak boleh menebak-nebak.
    'pratinjauUrl',
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

<div {{ $attributes->class(['pratinjau-kotak']) }} data-pratinjau-url="{{ $pratinjauUrl }}">
    {{-- Progress seksi + posisi seksi yang sedang aktif. --}}
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
        Badan pratinjau. Kosong sampai tab ini dibuka: isinya datang dari
        server setiap kali isian form berubah, dan server baru bisa tahu
        bentuk akhirnya setelah seluruh bab digabung menjadi satu teks.

        resources/js/materi-tambah.js yang mengisi wadah ini, lalu memasang
        pemilih seksi di atas hasilnya — sama seperti materi-detail.js
        melakukan pada halaman detail.
    --}}
    <div class="mt-4" data-preview-isi-wadah></div>
</div>