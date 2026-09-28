@extends('layouts.app')

@section('title', ($quiz ?? null ? 'Edit' : 'Buat').' Quiz | KelasKita')

@section('content')
    {{--
        Form "Buat Quiz" sekaligus "Edit Quiz".

        Dua mode ini sengaja satu halaman: isian, tujuan form, dan isi
        tombol yang berubah mengikuti $quiz. Baris soal memakai
        data-soal-baris supaya resources/js/quiz-tambah.js bisa menambah dan
        menghapus baris tanpa memuat ulang halaman.
    --}}
    @php
        $quiz = $quiz ?? null;
        $modeEdit = $quiz !== null;
        $soal = $soal ?? collect();

        /*
         * Isian baris soal: dari request lama kalau validasi gagal, kalau
         * tidak soal yang sudah tersimpan (mode edit), dan satu baris kosong
         * untuk quiz baru. Dihitung di sini karena dipakai dua kali: jumlah
         * soal di kepala kartu dan perulangan baris di bawahnya.
         */
        $baris = old('soal', $modeEdit
            ? $soal->map(fn ($s) => [
                'pertanyaan' => $s->pertanyaan,
                'pilihan_a' => $s->pilihan_a,
                'pilihan_b' => $s->pilihan_b,
                'pilihan_c' => $s->pilihan_c,
                'pilihan_d' => $s->pilihan_d,
                'jawaban_benar' => $s->jawaban_benar,
                'pembahasan' => $s->pembahasan,
                'tingkat_kesulitan' => $s->tingkat_kesulitan,
            ])->all()
            : [null]);

        $huruf = ['a', 'b', 'c', 'd'];
    @endphp

    <div data-tambah-quiz
        class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 pb-28 lg:-m-10 lg:p-10 lg:pb-28">

        {{-- =========================
             BREADCRUMB
        ========================== --}}
        <nav aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                <li>
                    <a href="{{ route('user.dashboard') }}"
                        class="text-muted transition hover:text-primary">Home</a>
                </li>

                <li class="text-primary/40" aria-hidden="true">/</li>

                {{-- Quiz dibuat dan dikelola lewat menu Karya Saya. --}}
                <li>
                    <a href="{{ route('user.karya-saya', ['tab' => 'quiz']) }}"
                        class="text-muted transition hover:text-primary">Karya Saya</a>
                </li>

                <li class="text-primary/40" aria-hidden="true">/</li>

                <li class="text-dark" aria-current="page">{{ $modeEdit ? 'Edit Quiz' : 'Buat Quiz' }}</li>
            </ol>
        </nav>

        {{-- =========================
             HEADER HALAMAN
        ========================== --}}
        <header data-reveal class="mt-4">
            <h1 class="text-2xl font-extrabold tracking-tight text-dark sm:text-3xl">
                {{ $modeEdit ? 'Edit Quiz' : 'Buat Quiz' }}
            </h1>

            <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-dark/60">
                @if ($modeEdit)
                    Perbaiki soal-soal quiz ini. Status tayang tidak berubah karena edits.
                @else
                    Susun soal-soalnya sendiri. Quiz baru menunggu persetujuan admin sebelum tayang ke semua pengguna.
                @endif
            </p>
        </header>

        @if ($modeEdit)
            {{-- Status quiz saat ini, supaya pemilik tahu apakah soal yang
                 diperbaiki sudah tayang atau masih menunggu admin. --}}
            <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-lavender px-3.5 py-1.5 text-xs font-bold text-primary">
                Status saat ini: {{ $quiz->labelStatus() }}
            </p>
        @endif

        @if ($errors->any())
            <div role="alert"
                class="mt-5 rounded-2xl border border-[#f4c7cd] bg-white px-4 py-3 text-sm text-[#a33a46] shadow-[0_14px_30px_-26px_rgba(33,26,58,0.5)]">
                <p class="font-semibold">Periksa lagi isianmu:</p>

                <ul class="mt-1.5 list-disc space-y-0.5 pl-5">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
            action="{{ $modeEdit ? route('user.quiz.update', $quiz->getKey()) : route('user.quiz.tambah.store') }}"
            class="mt-6 space-y-6">
            @csrf

            @if ($modeEdit)
                @method('PUT')
            @endif

            {{-- =========================
                 IDENTITAS QUIZ
            ========================== --}}
            <section data-reveal class="kartu-form overflow-hidden">
                <div class="kartu-form__kepala">
                    <span class="kartu-form__ikon" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </span>

                    <h2 class="kartu-form__judul">Informasi Quiz</h2>
                </div>

                <div class="kartu-form__badan space-y-4">
                    <div>
                        <label for="judul" class="label-form">Judul Quiz</label>

                        <input id="judul" name="judul" type="text" value="{{ old('judul', $quiz?->judul) }}" maxlength="120" required
                            placeholder="Contoh: HTML & CSS Dasar"
                            class="kolom-form mt-1.5 @error('judul') border-[#f4c7cd] @enderror">
                    </div>

                    <div>
                        <label for="deskripsi" class="label-form">Deskripsi <span class="font-normal text-dark/40">(opsional)</span></label>

                        <textarea id="deskripsi" name="deskripsi" rows="2" maxlength="220"
                            placeholder="Jelaskan singkat apa yang diuji quiz ini."
                            class="kolom-form mt-1.5 @error('deskripsi') border-[#f4c7cd] @enderror">{{ old('deskripsi', $quiz?->deskripsi) }}</textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="pelajaran_id" class="label-form">Kategori</label>

                            <div class="relative mt-1.5">
                                <select id="pelajaran_id" name="pelajaran_id" required
                                    class="kolom-form pilih-form @error('pelajaran_id') border-[#f4c7cd] @enderror">
                                    <option value="">Pilih kategori</option>

                                    @foreach ($kategori as $item)
                                        <option value="{{ $item->id }}" @selected((int) old('pelajaran_id', $quiz?->pelajaran_id) === $item->id)>
                                            {{ $item->nama }}
                                        </option>
                                    @endforeach
                                </select>

                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-dark/35"
                                    aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div>
                            <label for="durasi" class="label-form">Durasi <span class="font-normal text-dark/40">(menit, opsional)</span></label>

                            <input id="durasi" name="durasi" type="number" min="1" max="600" value="{{ old('durasi', $quiz?->durasi ?? 15) }}"
                                class="kolom-form mt-1.5 @error('durasi') border-[#f4c7cd] @enderror">
                        </div>
                    </div>

                    <fieldset>
                        <legend class="label-form">Siapa yang boleh mengerjakan?</legend>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-lavender bg-white px-3.5 py-2 text-sm font-semibold text-dark/70 has-checked:border-primary has-checked:bg-lavender has-checked:text-primary-dark">
                                <input type="radio" name="visibilitas" value="public"
                                    @checked(old('visibilitas', $quiz?->visibilitas ?? 'public') === 'public')
                                    class="h-3.5 w-3.5 accent-primary">
                                Publik
                            </label>

                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-lavender bg-white px-3.5 py-2 text-sm font-semibold text-dark/70 has-checked:border-primary has-checked:bg-lavender has-checked:text-primary-dark">
                                <input type="radio" name="visibilitas" value="private"
                                    @checked(old('visibilitas', $quiz?->visibilitas) === 'private')
                                    class="h-3.5 w-3.5 accent-primary">
                                Privat (pakai kode)
                            </label>
                        </div>
                    </fieldset>

                    {{-- Kolom kode hanya relevan untuk quiz privat, jadi
                         disembunyikan kalau radio "Privat" belum dipilih. --}}
                    <div data-kode-akses hidden @checked(old('visibilitas', $quiz?->visibilitas) === 'private')>
                        <label for="kode_akses" class="label-form">Kode Akses</label>

                        <input id="kode_akses" name="kode_akses" type="text" maxlength="20"
                            value="{{ old('kode_akses', $quiz?->kode_akses) }}"
                            placeholder="Contoh: KLS-2024"
                            class="kolom-form mt-1.5 @error('kode_akses') border-[#f4c7cd] @enderror">

                        <p class="mt-1.5 text-xs text-dark/45">
                            Minimal 4 karakter. Temanmu butuh kode ini untuk membuka quiz.
                        </p>
                    </div>
                </div>
            </section>

            {{-- =========================
                 DAFTAR SOAL
            ========================== --}}
            <section data-reveal class="kartu-form overflow-hidden">
                <div class="kartu-form__kepala">
                    <span class="kartu-form__ikon" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>

                    <h2 class="kartu-form__judul">Soal-soal</h2>

                    <span class="ml-auto text-xs font-semibold text-dark/45">
                        <span data-soal-jumlah>{{ max(1, count($baris)) }}</span> soal
                    </span>
                </div>

                <div class="kartu-form__badan">
                    <div data-soal-daftar class="space-y-4">
                        @foreach ($baris as $index => $isi)
                            <fieldset data-soal-baris
                                class="rounded-xl border border-lavender bg-brand-bg p-4">
                                <legend class="px-1 text-xs font-bold uppercase tracking-[0.12em] text-primary/70">
                                    Soal {{ $index + 1 }}
                                </legend>

                                <div class="space-y-3">
                                    <div>
                                        <label for="soal_{{ $index }}_pertanyaan" class="label-form">Pertanyaan</label>

                                        <textarea id="soal_{{ $index }}_pertanyaan" name="soal[{{ $index }}][pertanyaan]"
                                            rows="2" maxlength="255" required placeholder="Tulis pertanyaannya di sini."
                                            class="kolom-form mt-1.5">{{ $isi['pertanyaan'] ?? '' }}</textarea>
                                    </div>

                                    @foreach ($huruf as $h)
                                        <div>
                                            <label for="soal_{{ $index }}_pilihan_{{ $h }}"
                                                class="label-form !text-xs">Pilihan {{ strtoupper($h) }}</label>

                                            <input id="soal_{{ $index }}_pilihan_{{ $h }}"
                                                name="soal[{{ $index }}][pilihan_{{ $h }}]" type="text" maxlength="255" required
                                                placeholder="Isi pilihan {{ strtoupper($h) }}"
                                                class="kolom-form mt-1.5">
                                        </div>
                                    @endforeach

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label for="soal_{{ $index }}_jawaban_benar" class="label-form">Jawaban Benar</label>

                                            <div class="relative mt-1.5">
                                                <select id="soal_{{ $index }}_jawaban_benar"
                                                    name="soal[{{ $index }}][jawaban_benar]" required
                                                    class="kolom-form pilih-form">
                                                    @foreach ($huruf as $h)
                                                        <option value="{{ strtoupper($h) }}"
                                                            @selected(($isi['jawaban_benar'] ?? 'A') === strtoupper($h))>
                                                            {{ strtoupper($h) }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-dark/35"
                                                    aria-hidden="true">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="soal_{{ $index }}_tingkat_kesulitan" class="label-form">Tingkat Kesulitan</label>

                                            <div class="relative mt-1.5">
                                                <select id="soal_{{ $index }}_tingkat_kesulitan"
                                                    name="soal[{{ $index }}][tingkat_kesulitan]" required
                                                    class="kolom-form pilih-form">
                                                    @foreach (['Mudah', 'Sedang', 'Sulit'] as $tingkat)
                                                        <option value="{{ $tingkat }}"
                                                            @selected(($isi['tingkat_kesulitan'] ?? 'Mudah') === $tingkat)>
                                                            {{ $tingkat }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-dark/35"
                                                    aria-hidden="true">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="soal_{{ $index }}_pembahasan" class="label-form">
                                            Pembahasan <span class="font-normal text-dark/40">(opsional)</span>
                                        </label>

                                        <input id="soal_{{ $index }}_pembahasan" name="soal[{{ $index }}][pembahasan]"
                                            type="text" maxlength="500" placeholder="Penjelasan singkat kenapa jawaban itu benar."
                                            class="kolom-form mt-1.5" value="{{ $isi['pembahasan'] ?? '' }}">
                                    </div>
                                </div>

                                {{-- Quiz minimal satu soal, jadi baris terakhir
                                     tidak bisa dihapus. --}}
                                <button type="button" data-soal-hapus
                                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-dark/45 transition hover:text-[#c2414a] disabled:cursor-not-allowed disabled:opacity-40"
                                    @disabled($loop->last)>
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>

                                    Hapus soal ini
                                </button>
                            </fieldset>
                        @endforeach
                    </div>

                    <button type="button" data-soal-tambah
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-primary/40 bg-brand-bg px-4 py-3 text-sm font-bold text-primary transition hover:border-primary hover:bg-lavender/50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>

                        Tambah soal
                    </button>

                    @error('soal')
                        <p class="mt-2 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
                    @enderror

                    @error('soal.*.pertanyaan')
                        <p class="mt-2 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            {{-- =========================
                 ACTION BAR
            ==========================
                 Menempel di bawah layar supaya tombol simpan selalu
                 terjangkau tanpa menggulir melewati semua soal. --}}
            <div class="fixed inset-x-0 bottom-0 z-30 border-t border-lavender bg-white/90 backdrop-blur lg:left-64">
                <div class="flex items-center gap-3 px-6 py-3.5 lg:px-10">
                    <a href="{{ $modeEdit ? route('user.karya-saya', ['tab' => 'quiz']) : route('user.quiz') }}"
                        class="inline-flex items-center justify-center rounded-full border border-lavender px-5 py-2.5 text-sm font-semibold text-dark/70 transition hover:border-primary hover:text-primary-dark">
                        Batal
                    </a>

                    <button type="submit" class="tombol-materi ml-auto inline-flex items-center gap-2 rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 12 4.5l7.5 8.25M6 19.5h12a2.25 2.25 0 0 0 2.25-2.25V9.108a2.25 2.25 0 0 0-.659-1.591l-7.5-6.636a2.25 2.25 0 0 0-3.182 0l-7.5 6.636A2.25 2.25 0 0 0 4.5 9.108v8.142A2.25 2.25 0 0 0 6.75 19.5Z" />
                        </svg>

                        {{ $modeEdit ? 'Simpan Perubahan' : 'Simpan Quiz' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
