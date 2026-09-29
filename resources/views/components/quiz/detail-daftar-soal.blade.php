@props([
    // Daftar soal dari App\Support\DaftarSoal.
    'soal' => [],
    // Tautan "Lihat semua": daftar quiz lain di kategori yang sama.
    'tautan' => null,
])

{{--
    Seksi "Daftar Soal" di bawah kepala halaman detail quiz.

    Setiap soal hanya menampilkan inti pertanyaannya, dipotong dengan
    line-clamp supaya daftarnya tetap ringkas dan soal yang panjang tidak
    membuat baris melebar. Pilihan, jawaban benar, dan pembahasan sengaja
    tidak ikut (lihat App\Support\DaftarSoal), supaya halaman ini tidak
    membocorkan isi soal sebelum quiz dikerjakan.
--}}

<section {{ $attributes->class(['mt-6 sm:mt-7']) }} aria-labelledby="judul-daftar-soal">
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <h2 id="judul-daftar-soal" class="text-base font-extrabold tracking-tight text-dark sm:text-lg">
            Daftar Soal
        </h2>

        @if (filled($tautan))
            <a href="{{ $tautan }}" class="text-xs font-semibold text-primary transition hover:text-primary-dark">
                Lihat semua
            </a>
        @endif
    </div>

    @if (count($soal) === 0)
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
                <li class="daftar-soal__baris">
                    <span class="daftar-soal__nomor" aria-hidden="true">{{ $item['nomor'] }}</span>

                    <span class="daftar-soal__teks">{{ $item['pertanyaan'] }}</span>
                </li>
            @endforeach
        </ol>
    @endif
</section>
