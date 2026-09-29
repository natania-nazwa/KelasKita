@props([
    // Isian awal tiap soal: dari request lama kalau validasi gagal, kalau
    // tidak soal yang sudah tersimpan (mode edit), dan kosong untuk quiz
    // baru. Quiz baru selalu punya satu soal kosong supaya pengguna tidak
    // perlu menekan "+ Tambah Soal" untuk mulai.
    'baris' => [],

    // Apakah langkah ini yang langsung dibuka. Panel selainnya dirender
    // dengan atribut hidden supaya halaman tetap terbaca (dan tidak
    // menumpuk tiga panel) sebelum JavaScript berjalan.
    'terlihat' => false,
])

@php
    use App\Support\Ikon;
@endphp

{{--
    Langkah 2 wizard: Buat Soal.

    Isinya berurutan dari atas: panduan "Cara membuat soal", kartu form
    tempat seluruh kartu soal disusun, lalu baris tombol. Kepala halaman
    sengaja tidak ada — isian soalnya sendiri yang jadi fokus langkah ini,
    dan keduanya sudah cukup sebagai penanda halaman.

    Kartu soal di dalam langkah ini adalah builder: setiap soal punya
    pertanyaannya sendiri, tipenya sendiri, dan pilihannya sendiri. Bentuk
    kartunya ditulis di components/quiz/builder-soal, yang sekaligus jadi
    cetakan yang disalin JavaScript — jadi markup kartu hanya ada di satu
    tempat, baik untuk yang dirender server maupun yang dibuat di browser.

    Isian awal dikirim sebagai JSON, bukan dibaca ulang dari teks kartu,
    supaya pembahasan dan tingkat kesulitan per soal yang sengaja tidak
    tampil di kartu tidak ikut hilang.
--}}

<section data-wizard-panel="2" class="wizard-panel" aria-labelledby="judul-daftar-soal"
    @unless ($terlihat) hidden @endunless>

    {{-- =========================
         PANDUAN "CARA MEMBUAT SOAL"
    ==========================
         Murni panduan visual: tidak punya atribut data yang dipantau
         JavaScript dan tidak ikut dikirim ke server. --}}
    <section class="panduan" aria-labelledby="judul-panduan">
        <p class="panduan__label">Cara membuat soal</p>
        <h3 id="judul-panduan" class="panduan__judul">Buat soal dalam 4 langkah sederhana</h3>

        <ol class="panduan__daftar">
            @foreach ([
                ['icon' => 'file-teks', 'judul' => 'Tulis Pertanyaan', 'pesan' => 'Buat pertanyaan yang jelas dan mudah dipahami siswa.'],
                ['icon' => 'daftar', 'judul' => 'Pilih Tipe Jawaban', 'pesan' => 'Tentukan bagaimana siswa akan menjawab soal.'],
                ['icon' => 'centang', 'judul' => 'Isi Jawaban', 'pesan' => 'Masukkan pilihan jawaban dan tentukan jawaban yang benar.'],
                ['icon' => 'bintang', 'judul' => 'Atur Kesulitan', 'pesan' => 'Tandai tingkat kesulitan soal ini dan tambahkan pembahasan bila perlu.'],
            ] as $urutan => $langkah)
                <li class="panduan__langkah">
                    <span class="panduan__ikon" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ Ikon::path($langkah['icon']) }}" />
                        </svg>
                    </span>

                    <span class="panduan__nomor" aria-hidden="true">{{ $urutan + 1 }}</span>

                    <span class="panduan__teks">
                        <span class="panduan__nama">{{ $langkah['judul'] }}</span>
                        <span class="panduan__pesan">{{ $langkah['pesan'] }}</span>
                    </span>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- =========================
         KARTU FORM SOAL
    ========================== --}}
    <div class="kartu-form mt-5 overflow-hidden">
        <div class="kartu-form__kepala">
            <span class="kartu-form__ikon" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ Ikon::path('centang') }}" />
                </svg>
            </span>

            <div class="min-w-0">
                <h3 id="judul-daftar-soal" class="kartu-form__judul">Daftar Soal</h3>
                <p class="kartu-form__catatan">Setiap soal punya tipenya sendiri. Pilih tipe, lalu isi jawabannya.</p>
            </div>

            <span class="kartu-form__lencana" data-builder-jumlah>{{ count($baris) }} soal</span>
        </div>

        <div class="kartu-form__badan">
            {{-- Kosong: belum ada soal sama sekali. --}}
            <div class="soal-kosong" data-builder-kosong hidden>
                <span class="soal-kosong__ikon" aria-hidden="true">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </span>

                <p class="soal-kosong__judul">Belum ada soal</p>
                <p class="soal-kosong__pesan">Tambahkan minimal 1 soal sebelum melanjutkan.</p>
            </div>

            {{-- Kartu soal. --}}
            <div class="space-y-4" data-builder-daftar></div>

            <button type="button" class="tombol-tambah-soal mt-4" data-builder-tambah>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>

                Tambah Soal
            </button>

            <p class="galat-baris mt-2" data-builder-galat="daftar"
                data-galat="{{ $errors->first('soal') }}">{{ $errors->first('soal') }}</p>
        </div>
    </div>

    {{-- =========================
         BARIS TOMBOL
    ==========================
         Empat aksi langkah ini berkumpul di satu kartu: "Kembali" dan
         "Batal" untuk meninggalkan langkah, "Draft" untuk mengirim form
         lebih awal, lalu "Lanjut ke Pengaturan" yang didorong ke kanan.
         Baris navigasi bawah ikut disembunyikan selama langkah ini
         terbuka (resources/js/quiz-tambah.js), jadi tidak ada tombol yang
         muncul dua kali. --}}
    <div class="panel-aksi">
        <button type="button" class="tombol-garis" data-wizard-kembali-alias>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>

            Kembali
        </button>

        <a href="{{ route('user.karya-saya', ['tab' => 'quiz']) }}" class="tombol-garis" data-wizard-batal>Batal</a>

        <button type="button" class="tombol-garis" data-wizard-draft>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>

            Draft
        </button>

        <button type="button" class="tombol-utama" data-wizard-lanjut-alias>
            Lanjut ke Pengaturan

            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </button>
    </div>

    {{-- Cetakan kartu yang dipakai JavaScript. --}}
    <template data-builder-cetak>
        <x-quiz.builder-soal :soal="null" />
    </template>

    {{-- Cetakan satu baris pilihan jawaban. --}}
    <template data-builder-cetak-pilihan>
        <div class="builder-pilihan-baris" data-builder-pilihan-baris>
            <label class="builder-indikator" data-builder-indikator>
                <input type="radio" class="sr-only" data-builder-pilihan-benar>
            </label>

            <span class="builder-huruf" data-builder-huruf aria-hidden="true">A</span>

            <input type="text" maxlength="255" class="kolom-form builder-pilihan__kolom" data-builder-pilihan-teks
                placeholder="Tulis pilihan jawaban...">

            <span class="builder-benar" data-builder-benar-lencana hidden>
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>

                Jawaban Benar
            </span>

            <button type="button" class="builder-pilihan__hapus" data-builder-pilihan-hapus
                aria-label="Hapus pilihan ini" title="Hapus pilihan ini">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>

    {{-- Sumber isian awal untuk JavaScript. --}}
    <script type="application/json" data-builder-awal>@json(array_values($baris))</script>
</section>
