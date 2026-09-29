@props([
    // Daftar mata pelajaran (kategori) untuk dropdown.
    'kategori' => [],
    /*
     * Materi yang sedang diedit. Ada = mode edit: isian diisi dari materi
     * ini dan thumbnail yang sudah terlampir ditampilkan. Null = mode
     * tambah, isian kosong.
     */
    'materi' => null,
])

{{--
    Panel kiri halaman "Tambah Materi": data dasar materi.
    Semua field berada di dalam <form> utama halaman, jadi ikut
    terkirim saat tombol "Publikasikan Materi" / "Simpan Draft" ditekan.
--}}
<section {{ $attributes->class(['kartu-form']) }}>
    <header class="kartu-form__kepala">
        <span class="kartu-form__ikon" aria-hidden="true">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
            </svg>
        </span>

        <h2 class="kartu-form__judul">Informasi Materi</h2>
    </header>

    <div class="kartu-form__badan space-y-4">
        {{-- Judul materi + character counter --}}
        <div>
            <div class="flex items-center justify-between gap-3">
                <label for="nama" class="label-form">
                    Judul Materi <span class="text-ungu" aria-hidden="true">*</span>
                </label>

                <span data-judul-count class="text-[11px] font-semibold tabular-nums text-muted">0/100</span>
            </div>

            <input type="text" id="nama" name="nama" value="{{ old('nama', $materi?->nama) }}" required maxlength="100"
                placeholder="Contoh judul materi" data-judul-materi
                class="kolom-form mt-1.5">
        </div>

        {{-- Kategori + tingkat kesulitan --}}
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div>
                <label for="pelajaran_id" class="label-form">
                    Kategori <span class="text-ungu" aria-hidden="true">*</span>
                </label>

                <div class="relative mt-1.5">
                    <select id="pelajaran_id" name="pelajaran_id" required data-kategori
                        class="kolom-form pilih-form">
                        <option value="">Pilih kategori</option>

                        @foreach ($kategori as $item)
                            {{--
                                Ikon kategori ikut dibawa ke dalam <option>
                                supaya tab Preview bisa meniru lencana
                                kategori di halaman detail tanpa meminta
                                data ke server lagi.
                            --}}
                            @php
                                $ikonKategori = \App\Models\Pelajaran::warna($item->slug, $item->nama)['ikon'];
                            @endphp

                            <option value="{{ $item->id }}" data-ikon="{{ $ikonKategori }}"
                                @selected((int) old('pelajaran_id', $materi?->pelajaran_id) === $item->id)>
                                {{ $item->nama }}
                            </option>
                        @endforeach
                    </select>

                    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ungu"
                        fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </div>

            <div>
                <label for="tingkat_kesulitan" class="label-form">
                    Tingkat Kesulitan <span class="text-ungu" aria-hidden="true">*</span>
                </label>

                <div class="relative mt-1.5">
                <select id="tingkat_kesulitan" name="tingkat_kesulitan" required
                    data-kesulitan class="kolom-form pilih-form">
                            @foreach (['Mudah', 'Sedang', 'Sulit'] as $tingkat)
                                <option value="{{ $tingkat }}" @selected(old('tingkat_kesulitan', $materi?->tingkat_kesulitan ?? 'Mudah') === $tingkat)>
                                {{ $tingkat }}
                            </option>
                        @endforeach
                    </select>

                    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ungu"
                        fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Estimasi waktu belajar --}}
        <div>
            <label for="estimasi_waktu" class="label-form">
                Estimasi Waktu Belajar <span class="text-ungu" aria-hidden="true">*</span>
            </label>

            <input type="text" id="estimasi_waktu" name="estimasi_waktu"
                value="{{ old('estimasi_waktu', '10 menit') }}" required maxlength="40" placeholder="10 menit"
                class="kolom-form mt-1.5">
        </div>

        {{-- Thumbnail --}}
        <div>
            <span class="label-form">Thumbnail Materi</span>

            <div class="mt-1.5">
                <label class="area-unggah" for="thumbnail" data-thumbnail-drop>
                    <span class="mb-1 flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-ungu shadow-[0_10px_20px_-16px_rgba(124,77,255,0.9)]"
                        aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                        </svg>
                    </span>

                    <span class="text-sm font-semibold text-dark">Upload thumbnail</span>
                    <span class="text-xs text-muted">JPG, PNG, WEBP (maks. 100MB)</span>
                </label>

                <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp"
                    class="sr-only" data-thumbnail-input>

                {{-- Bingkai crop: gambar bisa dizoom dan digeser --}}
                <div class="relative hidden" data-thumbnail-preview>
                    <div class="thumbnail-bingkai" data-thumbnail-bingkai title="Geser gambar untuk mengatur posisi">
                        <img data-thumbnail-img alt="Preview thumbnail" class="thumbnail-gambar">

                        <button type="button" data-thumbnail-hapus aria-label="Hapus thumbnail"
                            class="absolute right-2 top-2 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/95 text-dark/60 shadow-[0_8px_18px_-12px_rgba(33,26,58,0.7)] transition hover:text-[#c2414a]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="thumbnail-kendali">
                        <button type="button" data-thumbnail-perkecil aria-label="Perkecil gambar"
                            class="kendali-zoom">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                            </svg>
                        </button>

                        <label for="thumbnail-zoom" class="sr-only">Ukuran gambar thumbnail</label>
                        <input type="range" id="thumbnail-zoom" min="100" max="300" step="5" value="100"
                            class="slider-ungu" data-thumbnail-zoom>

                        <button type="button" data-thumbnail-perbesar aria-label="Perbesar gambar"
                            class="kendali-zoom">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>

                        <span class="thumbnail-zoom" data-thumbnail-zoom-nilai>100%</span>

                        <button type="button" data-thumbnail-reset class="tombol-garis ml-auto px-3 py-1.5 text-xs">
                            Atur ulang
                        </button>
                    </div>

                    <p class="mt-1.5 text-[11px] leading-relaxed text-muted">
                        Geser gambar untuk memilih bagian yang ingin ditampilkan.
                    </p>
                </div>

                <p class="mt-1.5 text-xs font-medium text-[#c2414a] {{ $errors->has('thumbnail') ? '' : 'hidden' }}"
                    data-thumbnail-error role="alert">{{ $errors->first('thumbnail') }}</p>

                {{--
                    Penanda "hapus gambar lama". Diisi JavaScript saat tombol
                    Hapus ditekan, supaya berkasnya ikut dibuang dari disk
                    dan kolom thumbnail jadi kosong (lihat
                    User\MateriKelolaController::update). Tanpa bendera ini
                    tombol Hapus hanya berhenti di pratinjau browser.
                --}}
                <input type="hidden" name="thumbnail_hapus" value="{{ old('thumbnail_hapus', 0) }}"
                    data-thumbnail-hapus-flag>

                {{-- Mode edit: tampilkan thumbnail yang sekarang dipakai, supaya
                     pemilik tahu berkas mana yang akan diganti kalau ia
                     mengunggah yang baru, atau dihapus kalau ia menekan Hapus. --}}
                @if ($materi && filled($materi->thumbnail))
                    <div class="mt-2 flex items-center gap-3 rounded-xl border border-ungu-line bg-white p-2"
                        data-thumbnail-kini>
                        <img src="{{ \App\Support\BerkasMateri::url($materi->thumbnail) }}" alt="Thumbnail materi ini"
                            class="h-14 w-24 shrink-0 rounded-lg object-cover">

                        <p class="text-xs leading-relaxed text-muted">
                            Thumbnail yang sedang dipakai. Pilih berkas baru untuk menggantinya.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Tips / catatan --}}
        <div class="kartu-tips">
            <div class="flex gap-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-ungu shadow-[0_8px_18px_-14px_rgba(124,77,255,0.9)]"
                    aria-hidden="true">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                    </svg>
                </span>

                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-dark">Tips / Catatan</h3>

                    <p class="mt-1 text-xs leading-relaxed text-muted">
                        Catatan ini hanya untuk admin/guru. Akan ditampilkan di bagian bawah materi
                        saat dipublikasikan.
                    </p>
                </div>
            </div>

            <div class="mt-3">
                <label for="tips" class="sr-only">Tips / Catatan</label>

                <textarea id="tips" name="tips" rows="4" maxlength="500"
                    placeholder="Tambahkan catatan atau tips untuk siswa..." data-tips
                    class="kolom-form bg-white">{{ old('tips') }}</textarea>

                <p class="mt-1.5 text-right text-[11px] font-semibold tabular-nums text-muted" data-tips-count>
                    0/500
                </p>
            </div>
        </div>
    </div>
</section>
