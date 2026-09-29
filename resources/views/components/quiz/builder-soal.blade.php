@props([
    // Satu soal yang sedang disusun. Bentuknya:
    //   id, pertanyaan, tipe, pilihan = [{huruf, teks, benar}], kunciTeks, ceklis
    // Diawali satu soal kosong supaya pengguna tidak perlu menekan
    // "+ Tambah Soal" untuk mulai.
    'soal' => null,
])

@php
    use App\Models\Soal;

    /*
     * Kartu soal pada builder.
     *
     * Markup ini adalah cetakan yang disalin JavaScript setiap kali pengguna
     * menambah soal, menambah pilihan, atau menduplikasi soal, jadi bentuk
     * kartu hanya ditulis di satu tempat. Yang berubah tiap salinan hanyalah
     * teks isinya dan atribut data-*, semuanya ditulis ulang oleh
     * resources/js/quiz-builder.js.
     *
     * Field di dalam card TIDAK memakai name: isian baru disalin ke state
     * JavaScript, lalu dikirim sebagai input tersembunyi milik form utama
     * saat wizard disimpan. Kalau memakai name, peramban akan mengirim isian
     * yang belum tentu sudah lolos validasi.
     *
     * Susunannya mengikuti urutan pengerjaan: kepala (nomor soal, tingkat
     * kesulitan, dan tipe), pertanyaan, jawaban yang isinya berganti
     * mengikuti tipe, lalu pembahasan. Semuanya satu card utuh, bukan
     * tumpukan kartu kecil.
     */

    $tipe = Soal::normalkanTipe($soal['tipe'] ?? null);

    /*
     * Label tipe yang boleh dipilih baru.
     *
     * Tipe "dropdown" sengaja tidak ada di sini: di halaman mengerjakan soal
     * ia identik dengan pilihan ganda, hanya bentuk antarmukanya yang beda,
     * jadi tidak perlu ditawarkan lagi. Soal lama bertipe dropdown tetap
     * bisa diedit — resources/js/quiz-builder.js menyisipkan kembali opsinya
     * ke dropdown tipe ketika kartu seperti itu dibuka.
     */
    $labelTipe = [
        Soal::TIPE_PILIHAN_GANDA => 'Pilihan Ganda',
        Soal::TIPE_PILIHAN_BANYAK => 'Pilihan Ganda Kompleks',
        Soal::TIPE_BENAR_SALAH => 'Benar / Salah',
        Soal::TIPE_JAWABAN_SINGKAT => 'Isian Singkat',
        Soal::TIPE_PARAGRAF => 'Essay',
    ];

    /*
     * Judul bagian jawaban mengikuti tipe yang dipilih, karena isian tiap
     * tipe memang berbeda bentuk. resources/js/quiz-builder.js menulis ulang
     * judul ini setiap kali tipenya diganti.
     */
    $labelIsian = [
        Soal::TIPE_PILIHAN_GANDA => 'Pilihan Jawaban',
        Soal::TIPE_PILIHAN_BANYAK => 'Pilihan Jawaban',
        Soal::TIPE_BENAR_SALAH => 'Pilihan Jawaban',
        Soal::TIPE_DROPDOWN => 'Pilihan Jawaban',
        Soal::TIPE_JAWABAN_SINGKAT => 'Jawaban Benar',
        Soal::TIPE_PARAGRAF => 'Jawaban / Panduan Penilaian',
    ];
@endphp

<article class="builder-soal" data-builder-soal data-tipe="{{ $tipe }}">

    {{-- =========================
         KEPALA CARD
    ==========================
         Nomor soal di kiri bersama gagang seret; tingkat kesulitan dan
         tipe jawaban di kanan, bersebelahan supaya dua pengaturan per soal
         terbaca sebagai satu baris. Di layar sempit keduanya turun jadi
         barisnya sendiri supaya tidak pernah kecil dan tidak memicu scroll
         horizontal. --}}
    <header class="builder-soal__kepala">
        <div class="builder-soal__kiri">
            <button type="button" class="builder-soal__gaget" data-builder-seret
                title="Geser untuk mengurutkan soal" aria-hidden="true" tabindex="-1">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M9 6.75a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0ZM9 12a1.125 1.125 0 1 1-2.25 0A1.125 1.125 0 0 1 9 12Zm-1.125 6.75a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25ZM15.75 6.75a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0ZM15.75 12a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Zm-1.125 6.75a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25Z" />
                </svg>
            </button>

            <span class="builder-soal__nomor" data-builder-nomor>Soal 1</span>
        </div>

        <div class="builder-soal__kanan">
            {{--
                Tingkat kesulitan.

                Pindah ke kepala supaya isian kartu tinggal pertanyaan,
                jawaban, dan pembahasan. Selectnya tetap pemegang nilainya;
                labelnya jadi aria-label karena ruang kepala tidak cukup
                untuk label terlihat.
            --}}
            <div class="builder-tingkat">
                <select class="builder-tingkat__pilih" data-builder-tingkat aria-label="Tingkat Kesulitan soal"
                    title="Tingkat kesulitan soal ini">
                    @foreach (\App\Models\Quiz::tingkatKesulitan() as $tingkat)
                        <option value="{{ $tingkat }}" @selected($tingkat === \App\Models\Quiz::TINGKAT_MUDAH)>
                            {{ $tingkat }}
                        </option>
                    @endforeach
                </select>

                <span class="builder-tingkat__panah" aria-hidden="true">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </span>
            </div>

            {{--
                Pilih tipe jawaban.

                Select aslinya tetap jadi pemegang nilainya supaya seluruh logika
                yang sudah ada (ganti tipe, validasi, isian awal) tetap bekerja;
                tampilannya diganti tombol + menu di kanan, dan menu menulis balik
                nilai ke select lalu mengirim event change yang sama.
            --}}
            <div class="tipe-pilih" data-builder-tipe-pilih>
                <select class="sr-only tipe-pilih__asli" data-builder-tipe tabindex="-1"
                    aria-label="Tipe jawaban soal">
                    @foreach ($labelTipe as $nilai => $teks)
                        <option value="{{ $nilai }}" @selected($nilai === $tipe)>{{ $teks }}</option>
                    @endforeach
                </select>

                <button type="button" class="tipe-pilih__tombol" data-builder-tipe-tombol
                    aria-haspopup="listbox" aria-expanded="false" title="Pilih tipe jawaban">
                    <span class="tipe-pilih__ikon" data-builder-tipe-ikon aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </span>

                    <span class="tipe-pilih__teks" data-builder-tipe-teks>
                        {{ $labelTipe[$tipe] ?? Soal::labelTipe($tipe) }}
                    </span>

                    <svg class="tipe-pilih__panah" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                {{-- Isi menunya ditulis ulang oleh quiz-builder.js setiap kali
                     menu dibuka, supaya tipe lama yang tidak lagi ditawarkan
                     tetap muncul untuk soal yang memakainya. --}}
                <ul class="tipe-pilih__menu" data-builder-tipe-menu hidden role="listbox"
                    aria-label="Tipe jawaban"></ul>
            </div>
        </div>
    </header>

    <div class="builder-soal__badan">

        {{-- =========================
             PERTANYAAN
        ========================== --}}
        <section class="builder-blok">
            <label class="label-form">Pertanyaan <span class="wajib">*</span></label>

            <div class="builder-area">
                <textarea rows="3" maxlength="255" class="kolom-form" data-builder-pertanyaan
                    placeholder="Tulis pertanyaan..."></textarea>

                <span class="builder-area__hitung" data-builder-hitung aria-hidden="true">0/255</span>
            </div>

            <p class="builder-petunjuk">
                Tulis satu pertanyaan yang jelas dan mudah dipahami siswa.
            </p>

            <p class="galat-baris" data-builder-galat="pertanyaan"></p>
        </section>

        {{-- =========================
             JAWABAN
        ==========================
             Enam blok, satu per tipe. Bukan enam form, tapi satu blok
             tampilan yang hanya satu yang terlihat. Ini yang membuat
             pergantian tipe terasa langsung tanpa memuat ulang halaman.

             Judul bagiannya ikut berubah mengikuti tipe yang dipilih,
             ditulis oleh quiz-builder.js. --}}
        <section class="builder-blok">
            <label class="label-form">
                <span data-builder-langkah-judul="3">{{ $labelIsian[$tipe] }}</span>
                <span class="wajib" data-builder-tanda-wajib>*</span>
            </label>

            {{-- PILIHAN GANDA — radio, tepat satu jawaban benar. --}}
            <div data-builder-blok="{{ Soal::TIPE_PILIHAN_GANDA }}" @if ($tipe !== Soal::TIPE_PILIHAN_GANDA) hidden @endif>
                <div class="builder-pilihan" data-builder-pilihan></div>

                <button type="button" class="tombol-pilihan-tambah" data-builder-pilihan-tambah>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>

                    Tambah Pilihan
                </button>

                <p class="builder-petunjuk">
                    Tekan lingkaran di sebelah kiri untuk menandai satu jawaban yang benar.
                </p>

                <p class="galat-baris" data-builder-galat="pilihan"></p>
            </div>

            {{-- PILIHAN GANDA KOMPLEKS — checkbox, boleh lebih dari satu. --}}
            <div data-builder-blok="{{ Soal::TIPE_PILIHAN_BANYAK }}" @if ($tipe !== Soal::TIPE_PILIHAN_BANYAK) hidden @endif>
                <div class="builder-pilihan" data-builder-pilihan></div>

                <button type="button" class="tombol-pilihan-tambah" data-builder-pilihan-tambah>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>

                    Tambah Pilihan
                </button>

                <p class="builder-petunjuk">
                    Pilih satu atau lebih jawaban yang benar.
                </p>

                <p class="galat-baris" data-builder-galat="pilihan"></p>
            </div>

            {{--
                BENAR / SALAH — dua pilihan tetap, radio saja.

                Barisnya dibuat JavaScript dari konstanta di
                resources/js/quiz-builder.js, jadi teks "Benar" dan "Salah"
                tidak bisa diedit maupun dihapus pengguna.
            --}}
            <div data-builder-blok="{{ Soal::TIPE_BENAR_SALAH }}" @if ($tipe !== Soal::TIPE_BENAR_SALAH) hidden @endif>
                <div class="builder-pilihan" data-builder-pilihan></div>

                <p class="builder-petunjuk">
                    Pilih satu jawaban yang benar.
                </p>

                <p class="galat-baris" data-builder-galat="pilihan"></p>
            </div>

            {{-- ISIAN SINGKAT — satu kunci teks. --}}
            <div data-builder-blok="{{ Soal::TIPE_JAWABAN_SINGKAT }}" @if ($tipe !== Soal::TIPE_JAWABAN_SINGKAT) hidden @endif>
                <input type="text" maxlength="500" class="kolom-form" data-builder-kunci-teks
                    placeholder="Tulis jawaban yang benar...">

                <p class="galat-baris" data-builder-galat="kunciTeks"></p>

                {{--
                    Saklar "sama persis". Satu-satunya tempat di builder yang
                    menulis nilai ke input tersembunyi, supaya form tetap mengirim
                    0 ketika saklar dimatikan, bukan sekadar tidak mengirim apa pun.
                --}}
                <div class="builder-saklar mt-3">
                    <div class="min-w-0 flex-1">
                        <p class="builder-saklar__judul">Jawaban harus sama persis</p>
                        <p class="builder-saklar__pesan">
                            Kalau dimatikan, huruf besar dan spasi berlebih tidak berpengaruh.
                        </p>
                    </div>

                    <input type="hidden" data-builder-tokok-persis value="0">

                    <button type="button" class="saklar" role="switch" aria-checked="true"
                        data-builder-saklar></button>
                </div>

                <p class="builder-petunjuk">
                    Masukkan jawaban yang akan dipakai sistem untuk memeriksa jawaban siswa.
                </p>
            </div>

            {{-- ESSAY — panduan atau kunci jawaban, tidak dinilai otomatis. --}}
            <div data-builder-blok="{{ Soal::TIPE_PARAGRAF }}" @if ($tipe !== Soal::TIPE_PARAGRAF) hidden @endif>
                <textarea rows="5" maxlength="2000" class="kolom-form" data-builder-kunci-teks
                    placeholder="Tulis kunci atau panduan jawaban..."></textarea>

                <p class="builder-petunjuk">
                    Acuan pemeriksaan jawaban essay, boleh dikosongkan. Sistem belum menilai
                    essay secara otomatis.
                </p>
            </div>

            {{--
                Tipe dropdown: sudah tidak bisa dipilih lagi, tapi blok ini
                tetap ada supaya soal lama bertipe dropdown tidak rusak saat
                diedit. Kuncinya dibaca lewat select di bawah.
            --}}
            <div data-builder-blok="{{ Soal::TIPE_DROPDOWN }}"
                @if ($tipe !== Soal::TIPE_DROPDOWN) hidden @endif>
                <div class="builder-pilihan" data-builder-pilihan></div>

                <button type="button" class="tombol-pilihan-tambah" data-builder-pilihan-tambah>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>

                    Tambah Pilihan
                </button>

                <p class="galat-baris" data-builder-galat="pilihan"></p>
            </div>

            {{--
                Pemilih kunci jawaban khusus tipe dropdown. Tipe lain
                menandai kuncinya lewat radio di baris pilihan, jadi blok ini
                disembunyikan oleh resources/js/quiz-builder.js.
            --}}
            <div class="builder-ringkas-bungkus" data-builder-ringkas-bungkus hidden>
                <label class="label-form">Jawaban Benar <span class="wajib">*</span></label>

                <div class="pilih-bungkus" data-builder-pilihan-bungkus hidden>
                    <select class="kolom-form pilih-form" data-builder-pilih-benar>
                        <option value="">Pilih jawaban benar</option>
                    </select>

                    <span class="pilih-bungkus__panah" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </span>
                </div>
            </div>

            <p class="galat-baris" data-builder-galat="benar"></p>
        </section>

        {{-- =========================
             PEMBAHASAN
        ==========================
             Penjelasan per soal. Tidak ada kolom poin: nilai akhir quiz
             tetap dihitung dari persentase jawaban benar, dan tingkat
             kesulitannya dipilih di kepala card. --}}
        <section class="builder-blok">
            <label class="label-form">
                Pembahasan <span class="opsional">(opsional)</span>
            </label>

            <input type="text" maxlength="500" class="kolom-form" data-builder-pembahasan
                placeholder="Penjelasan singkat kenapa jawaban itu benar.">

            <p class="builder-petunjuk">Tampil ke siswa setelah quiz selesai.</p>
        </section>
    </div>

    {{-- =========================
         KAKI CARD
    ========================== --}}
    <footer class="builder-soal__kaki">
        <button type="button" class="tombol-garis" data-builder-duplikat>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m0 0h1.5a5.25 5.25 0 0 1 5.25-5.25H15.75" />
            </svg>

            Duplikat
        </button>

        <button type="button" class="tombol-garis tombol-garis--henti" data-builder-hapus>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>

            Hapus
        </button>
    </footer>
</article>
