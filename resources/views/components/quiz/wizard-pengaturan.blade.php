@props([
    'quiz' => null,
    'kodeAwal' => null,

    // Apakah langkah ini yang langsung dibuka. Panel selainnya dirender
    // dengan atribut hidden supaya halaman tetap terbaca (dan tidak
    // menumpuk tiga panel) sebelum JavaScript berjalan.
    'terlihat' => false,
])

@php
    /*
     * Langkah 3 wizard: Pengaturan.
     *
     * Tiga bagian: cara publikasi (publik / pakai kode), pengajuan ke admin,
     * dan saklar "Tampilkan Jawaban Setelah Selesai". Semuanya disimpan ke
     * kolom tb_quiz.visibilitas, tb_quiz.kode_akses, tb_quiz.status, dan
     * tb_quiz.tampilkan_jawaban.
     *
     * Kode quiz tidak disimpan untuk quiz publik, jadi kolomnya tetap
     * boleh diisi sebagai cadangan kalau nanti diubah ke mode kode.
     *
     * Saklar "Ajukan Persetujuan" hanya berarti sesuatu untuk quiz mode
     * publik. Quiz mode kode tidak pernah tayang untuk semua pengguna, jadi
     * tidak ada yang perlu disetujui admin dan saklarnya disembunyikan.
     */
    $terpilih = old('visibilitas', $quiz?->visibilitas ?? \App\Models\Quiz::VISIBILITAS_PUBLIK);
    $kode = old('kode_akses', $kodeAwal);
    $tampilkanJawaban = (bool) old('tampilkan_jawaban', $quiz?->tampilkan_jawaban ?? true);

    /*
     * Status quiz yang sedang diedit. Dua hal dibaca dari sini: apakah
     * pengajuan baru perlu catatan pendukung, dan apakah quiznya sudah
     * ditolak BATAS_PENGAJUAN_ULANG kali sehingga tidak bisa diajukan lagi.
     * Halaman tambah tidak punya $quiz, jadi kedua nilainya otomatis null dan
     * blok pengajuan diperlakukan sebagai quiz yang belum pernah dinilai.
     */
    $perluCatatan = $quiz !== null && $quiz->perluCatatanPengajuan();
    $bolehDiajukan = $quiz === null || $quiz->bolehDiajukan();
    $sudahDitolak = $quiz !== null && $quiz->catatan_admin !== null;

    // Gagal validasi atau nilai lama berarti isian ini sudah pernah diisi
    // pengguna, jadi tampilkan kembali tanpa perlu menyalakan saklarnya dulu.
    $catatanTerisi = $errors->has('catatan_pengajuan') || filled(old('catatan_pengajuan'));
    $tampilCatatan = $perluCatatan && ($catatanTerisi || old('publikasikan') === '1');

    /*
     * Saklar "Ajukan Persetujuan" mati secara bawaan, persis seperti di form
     * materi: pemilik tidak diam-diam mengirim quiz-nya ke daftar tunggu hanya
     * karena membuka form ini. Ia menyala sendiri kalau pengajuan sebelumnya
     * gagal validasi, supaya isian yang gagal itu tidak hilang tanpa jejak.
     */
    $diajukanAwal = $tampilCatatan;

    /*
     * Penjelasan di bawah saklar. Quiz yang sudah ditolak BATAS_PENGAJUAN_ULANG
     * kali tidak bisa diajukan lagi, jadi saklarnya dimatikan dan alasannya
     * ditulis di sini: tombol yang mati tanpa penjelasan hanya bikin pemilik
     * mengira formnya yang rusak.
     */
    $pesanPersetujuan = match (true) {
        ! $bolehDiajukan => 'Quiz ini sudah ditolak '.($quiz?->jumlah_ditolak ?? 0).'x dan tidak bisa diajukan lagi. Perubahannya tetap bisa disimpan sebagai draft.',
        $quiz !== null && $quiz->status === \App\Models\Quiz::STATUS_PUBLISHED => 'Quiz ini sudah tayang. Simpan dulu kalau ingin mengirim perubahannya ke admin untuk ditinjau lagi.',
        $quiz !== null && $quiz->status === \App\Models\Quiz::STATUS_PENDING => 'Quiz ini sedang ditinjau admin. Admin menilai versi yang sedang ditinjau.',
        default => 'Quiz tidak langsung tayang: admin meninjaunya dulu, lalu kamu tahu hasilnya di Karya Saya.',
    };
@endphp

<section data-wizard-panel="3" class="wizard-panel" aria-labelledby="judul-pengaturan"
    @unless ($terlihat) hidden @endunless>
    <div class="kartu-form overflow-hidden">
        <div class="kartu-form__kepala">
            <span class="kartu-form__ikon" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.204-.107-.398.165-.71.505-.78.929l-.15.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
            </span>

            <div class="min-w-0">
                <h2 id="judul-pengaturan" class="kartu-form__judul">Pengaturan Akhir</h2>
                <p class="kartu-form__catatan">Atur bagaimana quiz dikerjakan dan hasilnya dilihat.</p>
            </div>
        </div>

        <div class="kartu-form__badan space-y-6">
            {{-- =========================
                 CARA PUBLIKASI
            ========================== --}}
            <fieldset>
                <legend class="label-form mb-2.5">Pilih Cara Publikasi <span class="wajib">*</span></legend>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        ['nilai' => \App\Models\Quiz::VISIBILITAS_PUBLIK, 'judul' => 'Publikasikan', 'ikon' => \App\Support\Ikon::path('dunia'), 'pesan' => 'Quiz tayang di halaman Quiz untuk semua pengguna, setelah disetujui admin.'],
                        ['nilai' => \App\Models\Quiz::VISIBILITAS_PRIVAT, 'judul' => 'Gunakan Kode', 'ikon' => \App\Support\Ikon::path('gembok'), 'pesan' => 'Hanya yang mengetik kodenya yang bisa masuk. Tidak perlu persetujuan admin.'],
                    ] as $opsi)
                        <label class="publikasi-pilih" data-publikasi="{{ $opsi['nilai'] }}">
                            <input type="radio" name="visibilitas" value="{{ $opsi['nilai'] }}" class="sr-only"
                                @checked($terpilih === $opsi['nilai'])>

                            <span class="publikasi-pilih__ikon" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $opsi['ikon'] }}" />
                                </svg>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="publikasi-pilih__radio" aria-hidden="true"></span>
                                <span class="publikasi-pilih__judul">{{ $opsi['judul'] }}</span>
                                <span class="publikasi-pilih__pesan">{{ $opsi['pesan'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            {{-- =========================
                 KODE QUIZ
            ========================== --}}
            <div data-wizard-kode-area
                @if ($terpilih !== \App\Models\Quiz::VISIBILITAS_PRIVAT) hidden @endif>
                <label for="kode_akses" class="label-form">
                    Kode Quiz <span class="wajib" data-wizard-kode-wajib
                        @if ($terpilih !== \App\Models\Quiz::VISIBILITAS_PRIVAT) hidden @endif>*</span>
                </label>

                <div class="kode-bungkus mt-1.5">
                    <input id="kode_akses" name="kode_akses" type="text" value="{{ $kode }}" maxlength="20"
                        autocomplete="off" spellcheck="false" placeholder="K7F3P9" class="kolom-form kode-bungkus__kolom"
                        data-wizard-kode @if ($terpilih === \App\Models\Quiz::VISIBILITAS_PRIVAT) required @endif>

                    <button type="button" class="kode-bungkus__tombol" data-wizard-kode-acak
                        data-abjad="{{ \App\Support\KodeQuiz::ABJAD }}" data-panjang="{{ \App\Support\KodeQuiz::PANJANG }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992V4.356m-4.992 5.001V21M4.031 9.349a8.25 8.25 0 0 1 13.803-3.7M4.031 14.65a8.25 8.25 0 0 0 13.803 3.7" />
                        </svg>

                        Generate Kode
                    </button>
                </div>

                <p class="galat-baris" id="kode_akses-galat">@error('kode_akses') {{ $message }} @enderror</p>

                <p class="kolom-form__petunjuk">
                    Huruf dan angka acak, maksimal 20 karakter. Tekan Generate Kode untuk membuat yang baru.
                </p>
            </div>

            {{-- =========================
                 AJUKAN PERSETUJUAN
            ==========================
                 Hanya untuk quiz mode publik. Quiz mode kode tidak pernah
                 tayang untuk semua pengguna, jadi blok ini disembunyikan
                 bersama saat cara publikasi diganti ke "Gunakan Kode". --}}
            <div data-wizard-approval-area
                @if ($terpilih === \App\Models\Quiz::VISIBILITAS_PRIVAT) hidden @endif>
                <div class="pengaturan-saklar">
                    <div class="min-w-0 flex-1">
                        <label class="pengaturan-saklar__judul" for="saklar-publikasikan">
                            Ajukan Persetujuan Admin
                        </label>

                        <p class="pengaturan-saklar__pesan" data-wizard-approval-pesan>
                            {{ $pesanPersetujuan }}
                        </p>
                    </div>

                    {{--
                        Input tersembunyi selalu ada supaya form tetap mengirim
                        nilai 0 ketika saklar dimatikan, bukan sekadar tidak
                        mengirim apa pun. Saklarnya sendiri tombol, supaya
                        state bisa diubah tanpa memuat ulang halaman.
                    --}}
                    <input type="hidden" name="publikasikan" value="{{ $diajukanAwal ? '1' : '0' }}">

                    <button type="button" id="saklar-publikasikan" class="saklar" role="switch"
                        aria-checked="{{ $diajukanAwal ? 'true' : 'false' }}"
                        aria-label="Ajukan persetujuan admin"
                        @disabled(! $bolehDiajukan)
                        data-wizard-saklar-publikasikan></button>
                </div>

                {{-- Alasan penolakan admin, supaya pemilik tahu harus memperbaiki apa. --}}
                @if ($sudahDitolak && filled($quiz?->catatan_admin))
                    <div class="mt-3 rounded-xl border border-[#f4c7cd] bg-[#fdecee] px-3.5 py-2.5">
                        <p class="text-xs font-bold text-[#a8323c]">Alasan ditolak admin</p>

                        <p class="mt-1 text-sm leading-relaxed text-[#a8323c]">{{ $quiz->catatan_admin }}</p>

                        <p class="mt-1.5 text-xs text-[#a8323c]/80">
                            Sisa pengajuan: {{ $quiz->sisaPengajuan() }}x
                        </p>
                    </div>
                @endif

                {{--
                    Catatan pendukung hanya dimiliki quiz yang ditolak. Ia
                    disembunyikan dulu, lalu baru muncul setelah saklar
                    "Ajukan Persetujuan" dinyalakan, supaya alasan penolakan
                    yang dibaca lebih dulu tidak tenggelam di bawah isian yang
                    belum tentu diisi. Atribut hidden dilepas oleh
                    resources/js/quiz-tambah.js.
                --}}
                @if ($perluCatatan)
                    <div class="mt-3" data-catatan-wadah @unless ($tampilCatatan) hidden @endunless>
                        <label for="catatan-pengajuan" class="label-form">
                            Catatan pendukung <span class="wajib">(wajib)</span>
                        </label>

                        <textarea id="catatan-pengajuan" name="catatan_pengajuan" rows="2"
                            data-catatan-isian maxlength="500"
                            placeholder="Contoh: Soal nomor 4 dan 5 sudah saya perbaiki, dan tiap soal kini punya pembahasan."
                            class="kolom-form mt-1.5">{{ old('catatan_pengajuan', $quiz->catatan_pengajuan) }}</textarea>

                        <p class="galat-baris" id="catatan_pengajuan-galat">
                            @error('catatan_pengajuan') {{ $message }} @enderror
                        </p>
                    </div>
                @endif
            </div>

            {{-- =========================
                 DURASI + TAMPILKAN JAWABAN
            ==========================
                 Dua kolom supaya kartu pengaturan tidak tinggi sekali
                 di layar sempit. Kolom durasi selalu ada; isinya boleh
                 dikosongkan. --}}
            <div class="grid gap-4 sm:grid-cols-2 sm:items-start">
                <div>
                    <label for="durasi" class="label-form">
                        Durasi Pengerjaan <span class="opsional">(menit, opsional)</span>
                    </label>

                    <input id="durasi" name="durasi" type="number" min="1" max="600"
                        value="{{ old('durasi', $quiz?->durasi ?? 15) }}" inputmode="numeric"
                        class="kolom-form mt-1.5 @error('durasi') kolom-form--salah @enderror">

                    <p class="galat-baris" id="durasi-galat">@error('durasi') {{ $message }} @enderror</p>
                    <p class="kolom-form__petunjuk">1 sampai 600 menit. Kosongkan untuk tanpa batas waktu.</p>
                </div>

                {{-- Saklar "Tampilkan Jawaban Setelah Selesai". --}}
                <div class="pengaturan-saklar">
                    <div class="min-w-0 flex-1">
                        <label class="pengaturan-saklar__judul" for="saklar-jawaban">
                            Tampilkan Jawaban Setelah Selesai
                        </label>

                        <p class="pengaturan-saklar__pesan">
                            Siswa dapat melihat jawaban yang benar setelah mengerjakan.
                        </p>
                    </div>

                    {{--
                        Input tersembunyi selalu ada supaya form tetap mengirim
                        nilai 0 ketika saklar dimatikan, bukan sekadar tidak
                        mengirim apa pun. Nilainya ikut keadaan awal saklar di
                        atasnya, karena tombol "Draft" bisa mengirim form
                        langsung dari langkah Buat Soal — tanpa ini, saklar
                        yang terlihat menyala justru tersimpan mati.
                        Saklarnya sendiri tombol, supaya state bisa diubah
                        tanpa memuat ulang halaman.
                    --}}
                    <input type="hidden" name="tampilkan_jawaban" value="{{ $tampilkanJawaban ? '1' : '0' }}">

                    <button type="button" id="saklar-jawaban" class="saklar" role="switch"
                        aria-checked="{{ $tampilkanJawaban ? 'true' : 'false' }}"
                        aria-label="Tampilkan jawaban setelah selesai"
                        data-wizard-saklar-jawaban></button>
                </div>
            </div>

            {{-- Ringkasan singkat isi quiz sebelum disimpan. --}}
            <div class="kartu-tips" data-wizard-ringkasan>
                <p class="kartu-tips__judul">Siap disimpan</p>
                <ul class="kartu-tips__daftar" data-wizard-ringkasan-daftar></ul>
            </div>
        </div>
    </div>
</section>
