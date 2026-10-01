@props([
    // Daftar soal dari App\Support\DaftarSoal.
    'soal' => [],
    // Tautan "Lihat semua": daftar quiz lain di kategori yang sama.
    'tautan' => null,
    /*
     * Jumlah soal yang tampil sejak awal, sekaligus langkah tombol
     * "Lihat semua" di bawah daftar. Kirim 0 (atau null) kalau daftar
     * tidak mau dibatasi: seluruh soal langsung terlihat dan tombolnya
     * tidak ikut dirender.
     */
    'batas' => 5,
])

@php
    $batas = (int) $batas;
    $jumlahSoal = count($soal);

    /*
     * Masih ada baris soal yang tersembunyi di balik batas. Nilai ini
     * menentukan dua hal sekaligus: tautan "Lihat semua" di kepala dan
     * tombol buka/tutup di bawah daftar. Kalau soal habis dalam satu
     * tampilan (mis. quiz berisi lima soal), keduanya tidak dirender —
     * tidak ada yang bisa ditawarkan "Lihat semua"-nya.
     */
    $adaSisa = $batas > 0 && $jumlahSoal > $batas;
@endphp

{{--
    Seksi "Daftar Soal" di bawah kepala halaman detail quiz.

    Setiap soal hanya menampilkan inti pertanyaannya, dipotong dengan
    line-clamp supaya daftarnya tetap ringkas dan soal yang panjang tidak
    membuat baris melebar. Pilihan, jawaban benar, dan pembahasan sengaja
    tidak ikut (lihat App\Support\DaftarSoal), supaya halaman ini tidak
    membocorkan isi soal sebelum quiz dikerjakan.

    Pembatasan lima baris: baris keenam dan seterusnya sudah diberi
    atribut hidden oleh server, jadi tidak ada kilatan soal berikutnya
    sebelum JS sempat berjalan. Tombol di bawah daftar menambah lima baris
    setiap klik dan berubah jadi "Sembunyikan" begitu seluruh soal terlihat
    (lihat initDaftarSoal di resources/js/quiz-detail.js). Tanpa JS
    seluruh baris dibuka kembali lewat aturan noscript di layout, jadi
    tidak ada soal yang terkunci permanen.

    Tautan "Lihat semua" di kepala (daftar quiz satu kategori) ikut
    disembunyikan begitu tidak ada soal tersembunyi: teks yang sama muncul
    dua tempat dengan tujuan berbeda hanya membingungkan, dan pada quiz
    berisi lima soal tautan itu tidak menawarkan apa pun.
--}}

<section {{ $attributes->class(['mt-6 sm:mt-7']) }} aria-labelledby="judul-daftar-soal" data-daftar-soal>
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <h2 id="judul-daftar-soal" class="text-base font-extrabold tracking-tight text-dark sm:text-lg">
            Daftar Soal
        </h2>

        @if (filled($tautan) && $adaSisa)
            <a href="{{ $tautan }}" class="text-xs font-semibold text-primary transition hover:text-primary-dark">
                Lihat semua
            </a>
        @endif
    </div>

    @if ($jumlahSoal === 0)
        {{-- Quiz tanpa soal: kartu putih dengan garis putus-putus, supaya
             jelas memang belum ada isinya, bukan daftar yang gagal memuat
             data. --}}
        <div class="rounded-2xl border border-dashed border-lavender bg-white px-5 py-10 text-center">
            <h3 class="text-sm font-bold text-dark">Belum ada soal</h3>

            <p class="mx-auto mt-1.5 max-w-sm text-sm leading-relaxed text-dark/55">
                Quiz ini belum memiliki soal. Pembuatnya bisa menambahkannya lewat form tambah quiz.
            </p>
        </div>
    @else
        <ol class="daftar-soal kartu-detail">
            @foreach ($soal as $item)
                <li class="daftar-soal__baris"{{ $batas > 0 && $loop->index >= $batas ? ' hidden' : '' }}>
                    <span class="daftar-soal__nomor" aria-hidden="true">{{ $item['nomor'] }}</span>

                    <span class="daftar-soal__teks">{{ $item['pertanyaan'] }}</span>
                </li>
            @endforeach
        </ol>

        {{--
            Tombol buka/tutup daftar. Sengaja tanpa aria-expanded dan
            aria-controls: baris soal bukan akordion per soal, yang
            dilipat hanya jumlah baris yang terlihat.
        --}}
        @if ($adaSisa)
            <div class="mt-3 flex justify-end" data-daftar-soal-tombol data-batas="{{ $batas }}">
                <button type="button"
                    class="rounded-lg border border-lavender bg-white px-4 py-2 text-xs font-semibold text-primary transition hover:border-primary hover:bg-primary hover:text-white">
                    Lihat semua
                </button>
            </div>
        @endif
    @endif
</section>
