@props([
    // Jadwal yang sedang diedit. Null = mode tambah, isian kosong.
    'jadwal' => null,
    // Hari yang terpilih di halaman jadwal, dipakai sebagai nilai awal
    // dropdown hari di mode tambah. Null = pakai hari ini.
    'hariAktif' => null,
])

{{--
    Form tambah dan edit jadwal.

    Dua mode ini sengaja satu komponen: isian, tujuan form, dan isi tombol
    yang berubah mengikuti $jadwal. Tanpa itu, mengedit akan punya form kedua
    yang bisa menyimpang dari form tambah.

    Jam pakai input type="time" bawaan browser, jadi penulisan jamnya sudah
    divalidasi browser (termasuk 24 jam) sebelum dikirim. Jam yang bertumpuk
    tetap dicek ulang di server.

    PR boleh dikosongkan, tapi kalau diisi tanggal dikumpulkan wajib ikut,
    karena PR tanpa tenggat tidak bisa dikejar. Kelas dan ruang juga boleh
    kosong, dan yang kosong tidak disimpan sama sekali supaya tidak pernah
    muncul sebagai baris kosong di halaman jadwal.
--}}

@php
    $hariSekarang = (int) ($hariAktif ?? now()->dayOfWeek);
    $modeEdit = $jadwal !== null;

    $jamMulai = old('mulai', $modeEdit ? $jadwal->jamMulai() : '08:00');
    $jamSelesai = old('selesai', $modeEdit ? $jadwal->jamSelesai() : '09:30');
@endphp

<section {{ $attributes->class(['kartu-form']) }}>
    <header class="kartu-form__kepala">
        <span class="kartu-form__ikon" aria-hidden="true">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
            </svg>
        </span>

        <h2 class="kartu-form__judul">{{ $modeEdit ? 'Ubah Jadwal' : 'Detail Jadwal' }}</h2>
    </header>

    <div class="kartu-form__badan space-y-4">
        {{-- Hari --}}
        <div>
            <label for="hari" class="label-form">
                Hari <span class="text-ungu" aria-hidden="true">*</span>
            </label>

            <div class="relative mt-1.5">
                <select id="hari" name="hari" required class="kolom-form pilih-form">
                    @foreach (\App\Support\DaftarJadwal::pilihanHari() as $item)
                        <option value="{{ $item['nilai'] }}" @selected((int) old('hari', $jadwal?->hari ?? $hariSekarang) === $item['nilai'])>
                            {{ $item['nama'] }}
                        </option>
                    @endforeach
                </select>

                <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ungu"
                    fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </div>

            @error('hari')
                <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Jam --}}
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div>
                <label for="mulai" class="label-form">
                    Jam Mulai <span class="text-ungu" aria-hidden="true">*</span>
                </label>

                <input type="time" id="mulai" name="mulai" value="{{ $jamMulai }}" required
                    class="kolom-form mt-1.5">

                @error('mulai')
                    <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="selesai" class="label-form">
                    Jam Selesai <span class="text-ungu" aria-hidden="true">*</span>
                </label>

                <input type="time" id="selesai" name="selesai" value="{{ $jamSelesai }}" required
                    class="kolom-form mt-1.5">

                @error('selesai')
                    <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Pelajaran --}}
        <div>
            <label for="pelajaran" class="label-form">
                Mata Pelajaran <span class="text-ungu" aria-hidden="true">*</span>
            </label>

            <div class="relative mt-1.5">
                <select id="pelajaran" name="pelajaran" required class="kolom-form pilih-form">
                    <option value="">Pilih mata pelajaran</option>

                    @foreach (\App\Support\DaftarJadwal::pilihanPelajaran() as $item)
                        <option value="{{ $item['slug'] }}" @selected(old('pelajaran', $jadwal?->pelajaran) === $item['slug'])>
                            {{ $item['nama'] }}
                        </option>
                    @endforeach
                </select>

                <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ungu"
                    fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </div>

            @error('pelajaran')
                <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Nama pelajaran yang tampil di daftar --}}
        <div>
            <label for="judul" class="label-form">
                Nama Pelajaran <span class="text-ungu" aria-hidden="true">*</span>
            </label>

            <input type="text" id="judul" name="judul" value="{{ old('judul', $jadwal?->judul) }}" required
                maxlength="100" placeholder="Contoh: Pemrograman Web" class="kolom-form mt-1.5">

            <p class="mt-1.5 text-[11px] leading-relaxed text-muted">
                Boleh berbeda dari nama mata pelajaran, mis. "Matematika" ditulis "Matematika Peminatan".
            </p>

            @error('judul')
                <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Kelas dan ruang. Keduanya boleh dikosongkan; kalau kosong, tidak
             disimpan sama sekali dan tidak muncul di baris jadwal. --}}
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div>
                <label for="kelas" class="label-form">
                    Kelas <span class="text-muted">(opsional)</span>
                </label>

                <input type="text" id="kelas" name="kelas" value="{{ old('kelas', $jadwal?->kelas) }}"
                    maxlength="60" placeholder="Kosongkan kalau tidak perlu" class="kolom-form mt-1.5">

                @error('kelas')
                    <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="ruang" class="label-form">
                    Ruang <span class="text-muted">(opsional)</span>
                </label>

                <input type="text" id="ruang" name="ruang" value="{{ old('ruang', $jadwal?->ruang) }}"
                    maxlength="60" placeholder="Kosongkan kalau tidak perlu" class="kolom-form mt-1.5">

                @error('ruang')
                    <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- PR: apa yang harus dikerjakan dan kapan dikumpulkan --}}
        <div>
            <label for="pr" class="label-form">
                PR / Tugas <span class="text-muted">(opsional)</span>
            </label>

            <textarea id="pr" name="pr" rows="3" maxlength="500"
                placeholder="Contoh: PR halaman 45-50, tulislah 3 paragraf&#10;Atau: Kerjakan latihan 3 dan 4"
                class="kolom-form mt-1.5">{{ old('pr', $jadwal?->pr) }}</textarea>

            @error('pr')
                <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
            @enderror

            <div class="mt-3 sm:w-56">
                <label for="pr_dikumpulkan" class="label-form">
                    Dikumpulkan paling lambat
                </label>

                <input type="date" id="pr_dikumpulkan" name="pr_dikumpulkan"
                    value="{{ old('pr_dikumpulkan', $jadwal?->pr_dikumpulkan?->toDateString()) }}"
                    min="{{ now()->toDateString() }}" class="kolom-form mt-1.5">

                @error('pr_dikumpulkan')
                    <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <p class="mt-1.5 text-[11px] leading-relaxed text-muted">
                Boleh dikosongkan kalau jam pelajaran ini tidak ada PRnya. Kalau PRnya diisi, tanggal
                dikumpulkan ikut wajib.
            </p>
        </div>

        {{-- Catatan singkat --}}
        <div class="kartu-tips">
            <div class="flex gap-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-ungu shadow-[0_8px_18px_-14px_rgba(124,77,255,0.9)]"
                    aria-hidden="true">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                    </svg>
                </span>

                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-dark">Hanya yang perlu diisi</h3>

                    <p class="mt-1 text-xs leading-relaxed text-muted">
                        Wajib diisi: hari, jam mulai dan selesai, mata pelajaran, serta nama pelajaran.
                        Kelas, ruang, dan PR boleh dikosongkan, dan yang dikosongkan tidak akan tampil di
                        jadwalmu. Jam yang bertumpuk dengan pelajaran lain di hari yang sama akan ditolak,
                        jadi jadwalmu tidak pernah punya dua pelajaran sekaligus.
                    </p>                </div>
            </div>
        </div>
    </div>
</section>
