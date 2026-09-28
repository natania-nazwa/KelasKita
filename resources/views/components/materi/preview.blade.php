{{--
    Isi tab "Preview" pada kartu Daftar Bab: pratinjau tampilan siswa.

    Komponen ini hanya fragmen (tanpa kartu/header sendiri) karena
    berada di dalam kartu Daftar Bab. Semua isinya diisi JavaScript
    mengikuti bab yang sedang aktif pada editor.
--}}
<div {{ $attributes->class(['pratinjau-kotak']) }}>
    {{-- Progress bab + nomor halaman preview --}}
    <div class="flex items-center gap-3">
        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-ungu-bg" role="presentation">
            <div data-preview-bar class="h-full rounded-full bg-ungu transition-[width] duration-300"
                style="width: 100%"></div>
        </div>

        <span data-preview-indeks
            class="shrink-0 rounded-full bg-ungu-bg px-2.5 py-1 text-xs font-extrabold tabular-nums text-ungu">
            1 / 1
        </span>
    </div>

    <div class="pratinjau-frame mt-3">
        {{-- Thumbnail --}}
        <div class="pratinjau-banner" data-preview-thumbnail>
            <svg class="h-9 w-9 opacity-70" fill="none" stroke="currentColor" stroke-width="1.6"
                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
            </svg>

            <img data-preview-thumbnail-img alt="" class="absolute inset-0 hidden h-full w-full object-cover">
        </div>

        <div class="space-y-3 p-4">
            {{-- Kategori --}}
            <div class="flex flex-wrap items-center gap-2">
                <span data-preview-kategori
                    class="inline-flex max-w-full items-center gap-1.5 truncate rounded-full border border-ungu-line bg-ungu-bg px-2.5 py-1 text-[11px] font-extrabold text-ungu-dark">
                    Kategori
                </span>

                <span data-preview-bab-badge
                    class="hidden inline-flex items-center gap-1.5 rounded-full bg-lavender px-2.5 py-1 text-[11px] font-extrabold text-ungu-dark">
                    1 Bab
                </span>
            </div>

            {{-- Judul --}}
            <div>
                <h3 data-preview-judul class="text-base font-extrabold leading-snug tracking-tight text-dark">
                    Judul materi belum diisi
                </h3>
            </div>

            {{-- Tombol dengarkan --}}
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-preview-dengar
                    class="inline-flex items-center gap-2 rounded-full bg-ungu px-3.5 py-2 text-xs font-bold text-white transition hover:bg-ungu-dark">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                    </svg>

                    <span data-preview-dengar-label>Dengarkan materi</span>
                </button>

                <span data-preview-dengar-hint
                    class="hidden text-[11px] font-semibold text-muted">Upload audio terlebih dahulu.</span>
            </div>

            {{-- Navigasi bab pada preview (muncul bila materi punya banyak bab) --}}
            <div data-preview-outline class="pratinjau-outline hidden" role="navigation"
                aria-label="Daftar bab pada preview"></div>

            {{-- Isi bab aktif --}}
            <div class="border-t border-dashed border-ungu-line pt-3">
                <p data-preview-bab
                    class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-ungu-soft">Bab 1</p>

                <h4 data-preview-bab-judul class="mt-0.5 text-sm font-bold text-dark">Pendahuluan</h4>

                <div class="isi-materi pratinjau-isi mt-2 text-[13px] leading-relaxed" data-preview-isi></div>
            </div>
        </div>
    </div>

    {{-- Navigasi preview --}}
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
