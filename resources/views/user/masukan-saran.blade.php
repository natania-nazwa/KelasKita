@extends('layouts.app')

@section('title', 'Masukan & Saran | KelasKita')

@section('content')
    {{--
        Halaman "Masukan & Saran".

        Satu halaman merangkap form kirim: tidak ada daftar maupun riwayat,
        karena pengguna hanya perlu mengirim. Sesudah berhasil, halaman ini
        dirender ulang dari awal dengan pesan sukses, jadi formnya kembali
        kosong tanpa perlu JavaScript mengosongkannya.

        Halaman ini tidak memakai top bar (lihat $sembunyiTopbar di
        layouts/app), jadi kanvasnya setinggi satu layar penuh, sama seperti
        halaman Tambah Jadwal.
    --}}
    <div class="kanvas-jadwal -m-6 min-h-[100dvh] p-5 sm:p-6 lg:-m-10 lg:p-6 xl:p-8">
        <div class="mx-auto w-full max-w-3xl">

            {{-- =========================
                 JALAN PULANG (MOBILE)
            ========================== --}}
            {{-- Di layar < lg tidak ada sidebar, jadi halaman ini butuh jalan
                 pulang yang jelas ke dashboard. Di lg ke atas sidebar yang
                 memegang tugas itu, jadi tombolnya hilang dan breadcrumb di
                 bawah yang bekerja — keduanya tidak pernah tampil bersamaan.
                 Bentuk pil (tombol-garis), sama seperti halaman Jadwal: ia
                 berdiri sendiri di atas kanvas, jadi lebih terbaca sebagai
                 tombol daripada tautan teks biasa. --}}
            <div class="lg:hidden">
                <a href="{{ route('user.dashboard') }}" class="tombol-garis">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('panah-kiri') }}" />
                    </svg>

                    Kembali
                </a>
            </div>

            {{-- =========================
                 BREADCRUMB
            ========================== --}}
            <nav aria-label="Breadcrumb" class="hidden lg:block">
                <ol class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                    <li>
                        <a href="{{ route('user.dashboard') }}" class="text-muted transition hover:text-ungu">
                            Home
                        </a>
                    </li>

                    <li class="text-ungu-soft" aria-hidden="true">/</li>

                    <li class="text-dark" aria-current="page">Masukan &amp; Saran</li>
                </ol>
            </nav>

            {{-- =========================
                 HEADER HALAMAN
            ========================== --}}
            <header class="mt-4 flex items-start gap-4">
                <span class="mt-0.5 flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-ungu-line bg-ungu-bg text-ungu sm:h-14 sm:w-14"
                    aria-hidden="true">
                    <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" stroke-width="1.7"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('pesan') }}" />
                    </svg>
                </span>

                <div class="min-w-0">
                    <h1 class="text-2xl font-extrabold tracking-tight text-dark sm:text-3xl">
                        Masukan &amp; Saran
                    </h1>

                    <p class="mt-1 text-sm leading-relaxed text-muted sm:text-base">
                        Ada yang terasa kurang atau ingin diusulkan? Tulis di sini, ya. Masukanmu
                        membantu kami membuat KelasKita lebih nyaman dipakai.
                    </p>
                </div>
            </header>

            {{--
                Kabar berhasil mengirim dirender layout lewat komponen
                x-app.toast: kartu pojok kanan bawah yang menutup sendiri
                setelah lima detik dan punya tombol tutup (lihat
                resources/views/layouts/app.blade.php).
            --}}

            {{-- Ringkasan galat di atas form, supaya yang gagal tidak cuma
                 terlihat kalau scrolled sampai ke field-nya. --}}
            @if ($errors->any())
                <div role="alert"
                    class="mt-5 flex items-start gap-3 rounded-2xl border border-[#f3c9cb] bg-[#fdecee] px-4 py-3 text-sm font-medium text-[#a8323c]">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>

                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form id="form-masukan" method="POST" action="{{ route('user.masukan-saran.store') }}"
                enctype="multipart/form-data" class="mt-5" data-masukan-form>
                @csrf

                {{-- =========================
                     KARTU FORM
                ========================== --}}
                <section class="kartu-form">
                    <header class="kartu-form__kepala">
                        <span class="kartu-form__ikon" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('pesan') }}" />
                            </svg>
                        </span>

                        <h2 class="kartu-form__judul">Tulis Masukanmu</h2>
                    </header>

                    <div class="kartu-form__badan space-y-4">
                        {{-- Judul --}}
                        <div>
                            <label for="judul" class="label-form">
                                Judul <span class="text-ungu" aria-hidden="true">*</span>
                            </label>

                            <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required
                                maxlength="150" placeholder="Contoh: Tampilan materi sulit dibaca di HP"
                                class="kolom-form mt-1.5">

                            @error('judul')
                                <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Detail --}}
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <label for="detail" class="label-form">
                                    Detail Masukan <span class="text-ungu" aria-hidden="true">*</span>
                                </label>

                                <span data-masukan-hitung class="text-[11px] font-semibold text-muted">
                                    {{ mb_strlen((string) old('detail')) }}/500
                                </span>
                            </div>

                            <textarea id="detail" name="detail" rows="5" maxlength="500" required
                                placeholder="Jelaskan sedetail mungkin: apa yang terjadi, di halaman mana, dan menurutmu sebaiknya bagaimana."
                                class="kolom-form mt-1.5"
                                data-masukan-detail>{{ old('detail') }}</textarea>

                            @error('detail')
                                <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Lampiran --}}
                        <div>
                            <span class="label-form">
                                Lampiran <span class="text-muted">(opsional)</span>
                            </span>

                            <div class="mt-1.5">
                                <label class="area-unggah" for="lampiran">
                                    <span class="area-unggah__ikon" aria-hidden="true">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('unggah') }}" />
                                        </svg>
                                    </span>

                                    <span class="area-unggah__judul">Pilih berkas atau tarik ke sini</span>
                                    <span class="area-unggah__petunjuk">JPG, JPEG, PNG, atau PDF (maks. 5MB)</span>
                                </label>

                                <input type="file" id="lampiran" name="lampiran"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png,application/pdf"
                                    class="sr-only" data-masukan-lampiran>

                                {{-- Nama berkas yang dipilih, diisi initMasukanSaran(). --}}
                                <div data-masukan-lampiran-nama hidden
                                    class="mt-2 flex items-center gap-2 rounded-xl border border-ungu-line bg-ungu-bg px-3 py-2 text-xs font-semibold text-dark">
                                    <svg class="h-4 w-4 shrink-0 text-ungu" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                    </svg>

                                    <span class="min-w-0 truncate" data-masukan-lampiran-nama-teks></span>
                                </div>

                                @error('lampiran')
                                    <p class="mt-1.5 text-xs font-medium text-[#c2414a]" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
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
                                    <h3 class="text-sm font-bold text-dark">Biar cepat ditindaklanjuti</h3>

                                    <p class="mt-1 text-xs leading-relaxed text-muted">
                                        Tulis satu judul yang jelas, lalu jelaskan detailnya. Boleh lampirkan
                                        tangkapan layar lewat tombol di atas kalau ada tampilan yang ingin
                                        kamu tunjukkan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- =========================
                     KARTU AKSI

                     Terpisah dari kartu form, tapi tetap menunjuk ke form
                     yang sama lewat atribut form="form-masukan", jadi tidak
                     ada form kedua yang harus mengirim datanya sendiri.
                ========================== --}}
                <section class="kartu-form mt-4">
                    <div class="kartu-form__badan">
                        <p class="text-xs leading-relaxed text-muted">
                            Kirimanmu langsung diterima pengelola aplikasi untuk ditindaklanjuti.
                        </p>

                        <div class="mt-4 flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-end">
                            <button type="submit" form="form-masukan"
                                class="tombol-utama justify-center" data-masukan-kirim>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
                                    data-masukan-ikon>
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>

                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true" hidden
                                    data-masukan-ikon-muat>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>

                                <span data-masukan-label>Kirim Masukan</span>
                            </button>
                        </div>
                    </div>
                </section>
            </form>
        </div>
    </div>
@endsection
