@props([
    'kategori',
    'quiz' => null,

    /*
     * Munculkan kolom durasi dan saklar "tampilkan jawaban".
     *
     * Dua-duanya milik form pemilik, tapi diletakkan di langkah
     * "Pengaturan" yang tidak ada di form admin. Karena form admin hanya punya
     * dua tahap, keduanya ikut dipindah ke tahap pertama di sana — lewat prop
     * ini, bukan dengan menyalin komponennya.
     */
    'durasi' => false,
])

@php
    /*
     * Langkah 1 wizard: Informasi Dasar.
     *
     * Kolom thumbnail memakai nama "thumbnail" dan tetap opsional: quiz
     * tanpa gambar tetap tampil dengan gradasi warna kategori di kartu
     * (lihat .kartu-quiz__gambar).
     *
     * old() selalu menang, jadi submit yang gagal validasi tidak
     * membiarkan isian yang sudah diklik pengguna hilang.
     */
    $thumbnailLama = $quiz?->thumbnail ? \App\Support\BerkasQuiz::url($quiz->thumbnail) : null;
    $galatThumbnail = $errors->first('thumbnail');
@endphp

<section data-wizard-panel="1" class="wizard-panel" aria-labelledby="judul-informasi">
    <div class="kartu-form overflow-hidden">
        <div class="kartu-form__kepala">
            <span class="kartu-form__ikon" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </span>

            <div class="min-w-0">
                <h2 id="judul-informasi" class="kartu-form__judul">Informasi Dasar</h2>
                <p class="kartu-form__catatan">Keterangan utama quiz yang akan kamu buat.</p>
            </div>
        </div>

        <div class="kartu-form__badan space-y-5">
            <div>
                <label for="judul" class="label-form">Judul Quiz <span class="wajib">*</span></label>

                <input id="judul" name="judul" type="text" value="{{ old('judul', $quiz?->judul) }}" maxlength="120"
                    placeholder="Contoh: HTML Dasar" autocomplete="off"
                    class="kolom-form mt-1.5 @error('judul') kolom-form--salah @enderror">

                <p class="galat-baris" id="judul-galat">@error('judul') {{ $message }} @enderror</p>
                <p class="kolom-form__petunjuk">Maksimal 120 karakter.</p>
            </div>

            <div>
                <label for="deskripsi" class="label-form">Deskripsi <span class="wajib">*</span></label>

                <textarea id="deskripsi" name="deskripsi" rows="4" maxlength="220"
                    placeholder="Jelaskan tentang quiz ini..."
                    class="kolom-form mt-1.5 @error('deskripsi') kolom-form--salah @enderror">{{ old('deskripsi', $quiz?->deskripsi) }}</textarea>

                <p class="galat-baris" id="deskripsi-galat">@error('deskripsi') {{ $message }} @enderror</p>
                <p class="kolom-form__petunjuk">Ringkasan singkat, maksimal 220 karakter.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="pelajaran_id" class="label-form">Kategori <span class="wajib">*</span></label>

                    <div class="pilih-bungkus mt-1.5">
                        <select id="pelajaran_id" name="pelajaran_id" required
                            class="kolom-form pilih-form @error('pelajaran_id') kolom-form--salah @enderror">
                            <option value="">Pilihan kategori</option>

                            @foreach ($kategori as $item)
                                <option value="{{ $item->id }}" @selected((int) old('pelajaran_id', $quiz?->pelajaran_id) === $item->id)>
                                    {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>

                        <span class="pilih-bungkus__panah" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </span>
                    </div>

                    <p class="galat-baris" id="pelajaran_id-galat">@error('pelajaran_id') {{ $message }} @enderror</p>
                    <p class="kolom-form__petunjuk">Ambil dari daftar mata pelajaran KelasKita.</p>
                </div>

                <div>
                    <label for="tingkat_kesulitan" class="label-form">
                        Tingkat Kesulitan <span class="wajib">*</span>
                    </label>

                    @php $kesulitanAwal = old('tingkat_kesulitan', $quiz?->tingkat_kesulitan ?? \App\Models\Quiz::TINGKAT_MUDAH); @endphp

                    <div class="pilih-bungkus mt-1.5">
                        <select id="tingkat_kesulitan" name="tingkat_kesulitan" required
                            class="kolom-form pilih-form @error('tingkat_kesulitan') kolom-form--salah @enderror">
                            <option value="">Pilih tingkat</option>

                            @foreach (\App\Models\Quiz::tingkatKesulitan() as $tingkat)
                                <option value="{{ $tingkat }}" @selected($kesulitanAwal === $tingkat)>{{ $tingkat }}</option>
                            @endforeach
                        </select>

                        <span class="pilih-bungkus__panah" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </span>
                    </div>

                    <p class="galat-baris" id="tingkat_kesulitan-galat">
                        @error('tingkat_kesulitan') {{ $message }} @enderror
                    </p>

                    <p class="kolom-form__petunjuk">Berlaku untuk keseluruhan quiz ini.</p>
                </div>
            </div>

            @if ($durasi)
                {{--
                    Durasi dan saklar jawaban. Markupnya sama persis dengan
                    yang dipakai langkah Pengaturan di form pemilik, supaya
                    nilai yang tersimpan tidak pernah berbeda tergantung form
                    mana yang membukanya — termasuk input tersembunyi yang
                    membuat form tetap mengirim 0 ketika saklarnya dimatikan.
                --}}
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

                    <div class="pengaturan-saklar">
                        <div class="min-w-0 flex-1">
                            <label class="pengaturan-saklar__judul" for="saklar-jawaban">
                                Tampilkan Jawaban Setelah Selesai
                            </label>

                            <p class="pengaturan-saklar__pesan">
                                Siswa dapat melihat jawaban yang benar setelah mengerjakan.
                            </p>
                        </div>

                        @php
                            $tampilkanJawaban = filter_var(
                                old('tampilkan_jawaban', $quiz?->tampilkan_jawaban ?? true),
                                FILTER_VALIDATE_BOOLEAN
                            );
                        @endphp

                        <input type="hidden" name="tampilkan_jawaban" value="{{ $tampilkanJawaban ? '1' : '0' }}">

                        <button type="button" id="saklar-jawaban" class="saklar" role="switch"
                            aria-checked="{{ $tampilkanJawaban ? 'true' : 'false' }}"
                            aria-label="Tampilkan jawaban setelah selesai"
                            data-wizard-saklar-jawaban></button>
                    </div>
                </div>
            @endif

            {{-- =========================
                 THUMBNAIL
            ========================== --}}
            <div>
                <label for="thumbnail" class="label-form">Thumbnail <span class="opsional">(opsional)</span></label>

                {{--
                    Input berkas disembunyikan dan dipicu lewat tombol
                    "Pilih gambar", supaya area unggah bisa sendiri tanpa
                    harus menekan input file yang tampilannya bawaan browser.
                --}}
                <input id="thumbnail" name="thumbnail" type="file" accept="image/jpeg,image/png,image/webp"
                    class="sr-only" data-wizard-thumbnail-input>

                {{-- Area kosong. --}}
                <div class="area-unggah mt-1.5 min-h-[10.5rem]" data-wizard-thumbnail-area>
                    <span class="area-unggah__ikon" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </span>

                    <p class="area-unggah__judul">Pilih gambar atau upload</p>
                    <p class="area-unggah__petunjuk">disarankan ukuran 16:9, maksimal 5MB</p>

                    <button type="button" class="tombol-garis mt-3" data-wizard-thumbnail-pilih>
                        Pilih gambar
                    </button>
                </div>

                {{-- Area terisi: pratinjau + tombol ganti dan hapus. --}}
                <div class="mt-1.5" data-wizard-thumbnail-isi @unless ($thumbnailLama) hidden @endunless>
                    <div class="thumbnail-bingkai">
                        <img data-wizard-thumbnail-img class="thumbnail-gambar" alt="Pratinjau thumbnail quiz"
                            src="{{ $thumbnailLama ?? '' }}" @unless ($thumbnailLama) hidden @endunless>
                    </div>

                    <div class="thumbnail-kendali">
                        <button type="button" class="tombol-garis" data-wizard-thumbnail-pilih>
                            Ganti gambar
                        </button>

                        <button type="button" class="tombol-garis tombol-garis--henti" data-wizard-thumbnail-hapus>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>

                            Hapus
                        </button>

                        <p class="kolom-form__petunjuk min-w-0 flex-1 truncate text-right" data-wizard-thumbnail-nama></p>
                    </div>
                </div>

                {{--
                    Penanda "hapus gambar lama". Diisi oleh JavaScript saat
                    pengguna menekan tombol Hapus pada thumbnail yang sudah
                    tersimpan, supaya berkasnya ikut terhapus dari disk
                    (lihat User\QuizKelolaController::update).

                    Nilainya memakai old() supaya permintaan hapus tidak
                    hilang kalau validasi server menolak kiriman lain.
                --}}
                <input type="hidden" name="thumbnail_hapus" value="{{ old('thumbnail_hapus', 0) }}"
                    data-wizard-thumbnail-hapus-flag>

                {{--
                    Galat thumbnail selalu ada di DOM, meski kosong: JavaScript
                    menolak berkas yang kebesaran atau formatnya salah lewat
                    elemen ini, dan tampilGalat() diam saja kalau elemennya
                    tidak ditemukan — pesannya jadi hilang tanpa jejak.
                --}}
                <p class="galat-baris" data-wizard-thumbnail-galat @unless ($galatThumbnail) hidden @endunless>
                    {{ $galatThumbnail }}
                </p>
            </div>
        </div>
    </div>
</section>
