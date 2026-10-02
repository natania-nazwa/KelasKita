@extends('layouts.app')

@section('title', ($materi ?? null ? 'Edit' : 'Tambah').' Materi | KelasKita')

@section('content')
    {{--
        Halaman "Tambah Materi" sekaligus "Edit Materi".

        Dua mode ini sengaja satu halaman dan satu set komponen
        (informasi, daftar bab, editor): isian, tujuan form, dan isi tombol
        yang berubah mengikuti $materi. Tanpa itu, mengedit akan punya form
        kedua yang bisa menyimpang dari form tambah.

        Semua interaksi bab/editor/preview dijalankan oleh
        resources/js/materi-tambah.js.
    --}}
    @php
        $materi = $materi ?? null;
        $modeEdit = $materi !== null;
    @endphp

    {{-- Top bar halaman ini disembunyikan (lihat $sembunyiTopbar di
         layouts/app), jadi kanvas tidak lagi didorong oleh baris setinggi
         4rem dan langsung setinggi layar. --}}
    <div data-tambah-materi
        class="kanvas-materi -m-6 min-h-[100dvh] p-6 pb-0 lg:-m-10 lg:p-10 lg:pb-0">

        {{-- =========================
             HEADER HALAMAN
        ==========================
             Breadcrumb "Home / Karya Saya / Tambah Materi" dihapus: top bar
             halaman ini sudah disembunyikan, jadi jalur pulang-nya Ñ‚ÐµÐ¿ÐµÑ€ÑŒ
             ditangani tombol "Kembali" di kanan judul. --}}
        <header class="flex flex-wrap items-start justify-between gap-4">
            {{-- Ikon + judul + penjelas jadi satu blok, supaya justify-between
                 di header cuma menyisakan ruang di antara blok ini dan
                 tombol Kembali, bukan memencah ikon dari judul. --}}
            <div class="flex min-w-0 items-start gap-4">
                <span class="mt-0.5 flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-ungu-line bg-ungu-bg text-ungu sm:h-14 sm:w-14"
                    aria-hidden="true">
                    <svg class="h-6 w-6 sm:h-7 sm:w-7" fill="none" stroke="currentColor" stroke-width="1.7"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </span>

                <div class="min-w-0">
                    <h1 class="text-2xl font-extrabold tracking-tight text-dark sm:text-3xl">
                        {{ $modeEdit ? 'Edit Materi' : 'Tambah Materi' }}
                    </h1>

                    <p class="mt-1 text-sm leading-relaxed text-muted sm:text-base">
                        {{ $modeEdit
                            ? 'Perbaiki isi materi ini. Materi tetap milikmu dan bisa kamu ubah kapan saja.'
                            : 'Buat materi pembelajaran untuk siswa.' }}
                    </p>
                </div>
            </div>

            {{-- Top bar halaman ini disembunyikan (lihat $sembunyiTopbar di
                 layouts/app), jadi "Kembali" menggantikan cara pulang biasa:
                 tombolnya duduk di kanan atas, lurus dengan judul. --}}
            <a href="{{ route('user.karya-saya', ['tab' => 'materi']) }}"
                class="tombol-garis ml-auto shrink-0 self-start py-2">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>

                Kembali
            </a>
        </header>

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

        <form id="form-tambah-materi"
            action="{{ $modeEdit ? route('user.materi.update', $materi->slug) : route('user.materi.tambah.store') }}"
            method="POST" enctype="multipart/form-data" class="mt-5">
            @csrf

            @if ($modeEdit)
                @method('PUT')
            @endif

            {{-- =========================
                 SATU KOLOM (kartu bertumpuk).
                 Preview jadi tab di dalam kartu Daftar Bab,
                 jadi tidak ada kartu preview terpisah.
            ========================== --}}
            <div class="grid min-w-0 gap-4 lg:gap-5">
                {{-- Informasi materi --}}
                <x-materi.informasi :kategori="$kategori" :materi="$materi" />

                {{-- Daftar bab + preview (tab) --}}
                <x-materi.bab :kategori="$kategori" :materi="$materi" :pratinjau-url="route('user.materi.pratinjau')" />

                {{-- Editor isi materi --}}
                <x-materi.editor :isi="$modeEdit ? $materi->isi : null" />
            </div>

            {{-- Data yang disusun JavaScript sebelum form dikirim --}}
            <input type="hidden" name="isi" value="{{ old('isi', $modeEdit ? $materi->isi : '') }}" data-input-isi>
            <input type="hidden" name="bab" value="{{ old('bab', $modeEdit ? json_encode($bab ?? [], JSON_UNESCAPED_UNICODE) : '') }}"
                data-input-bab>

            {{-- =========================
                 ACTION BAR
            ==========================
                 Baris tombol hanya muncul setelah halaman di-scroll ke
                 bawah, jadi selama form sedang diisi tidak ada yang
                 menutupi isian. Yang menandainya sudah sampai bawah
                 adalah pikuan setinggi 1px tepat di atasnya, diamati
                 oleh materi-tambah.js.
            ========================== --}}
            <div data-action-bar-picu class="h-px" aria-hidden="true"></div>

            <div data-action-bar
                class="sticky bottom-0 z-30 -mx-6 mt-5 border-t border-ungu-line bg-white/95 px-6 py-4 shadow-[0_-16px_32px_-30px_rgba(49,46,129,0.7)] backdrop-blur lg:-mx-10 lg:px-10">
                @php
                    /*
                     * Status materi yang sedang diedit, dipakai untuk kalimat
                     * penjelasan dan label tombol sekaligus supaya keduanya
                     * tidak pernah berbeda pendapat.
                     */
                    $sedangDipublikasi = $modeEdit
                        && $materi->status === \App\Models\Materi::STATUS_PUBLISHED;
                    $bolehDiajukan = $modeEdit && $materi->bolehDiajukan();
                    $labelAjukan = $sedangDipublikasi ? 'Kirim Ulang ke Review' : 'Ajukan Persetujuan';
                @endphp

                {{-- Blok kiri: status materi + penjelasannya.
                     Penolakan dan catatan pengajuan diletakkan di bawah blok ini,
                     bukan di dalam baris tombol, supaya isian yang panjang tidak
                     ikut jadi anak flex row yang isinya cuma tombol. --}}
                <div class="min-w-0">
                    @if ($modeEdit)
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="karya-status karya-status--{{ $materi->warnaStatus() }}">
                                <span class="karya-status__titik" aria-hidden="true"></span>
                                {{ $materi->labelStatus() }}
                            </span>
                        </div>

                        <p class="mt-1.5 text-sm leading-relaxed text-muted">
                            @if ($materi->status === \App\Models\Materi::STATUS_PENDING)
                                Materi ini sedang ditinjau admin. Perubahannya tetap bisa
                                disimpan, tapi admin menilai versi yang sedang ditinjau.
                            @elseif ($sedangDipublikasi)
                                Materi ini sudah tayang. Perubahan baru tidak langsung
                                tayang: tekan "Kirim Ulang ke Review" supaya admin
                                menilai perubahannya dulu.
                            @elseif (! $bolehDiajukan)
                                Materi ini sudah ditolak {{ $materi->jumlah_ditolak }}x dan
                                tidak bisa diajukan lagi. Perubahannya tetap bisa disimpan.
                            @else
                                Perubahanmu akan langsung tersimpan ke materi ini.
                            @endif
                        </p>
                    @else
                        <div class="flex min-w-0 items-start gap-3">
                            <button type="button" role="switch" aria-checked="true" data-publikasikan
                                class="saklar mt-0.5" aria-label="Ajukan persetujuan materi"></button>

                            <div class="min-w-0">
                                <p class="text-sm font-bold text-dark">Ajukan persetujuan</p>

                                <p class="text-xs leading-relaxed text-muted">
                                    Materi tidak langsung tayang: admin meninjaunya dulu,
                                    lalu kamu tahu hasilnya di Karya Saya.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Alasan penolakan + catatan pengajuan.
                     Hanya untuk materi yang masih boleh diajukan. --}}
                @if ($bolehDiajukan)
                    @if (filled($materi->catatan_admin))
                        <div class="mt-3 rounded-xl border border-[#f4c7cd] bg-[#fdecee] px-4 py-3">
                            <p class="text-xs font-bold text-[#a8323c]">
                                Alasan ditolak admin
                            </p>

                            <p class="mt-1 text-sm leading-relaxed text-[#a8323c]">
                                {{ $materi->catatan_admin }}
                            </p>

                            <p class="mt-1.5 text-xs text-[#a8323c]/80">
                                Sisa pengajuan: {{ $materi->sisaPengajuan() }}x
                            </p>
                        </div>
                    @endif

                    {{-- Catatan pendukung hanya dimiliki materi yang ditolak.
                         Ia disembunyikan dulu, lalu baru muncul setelah tombol
                         "Ajukan Persetujuan" ditekan, supaya alasan penolakan
                         yang dibaca lebih dulu tidak tenggelam di bawah isian
                         yang belum tentu diisi. Atribut hidden dilepas oleh
                         materi-tambah.js. --}}
                    @if ($materi->perluCatatanPengajuan())
                        @php
                            // Gagal validasi atau nilai lama berarti isian ini
                            // sudah pernah diisi pengguna, jadi tampilkan
                            // kembali tanpa perlu menekan tombol sekali lagi.
                            $tampilCatatan = $errors->has('catatan_pengajuan')
                                || filled(old('catatan_pengajuan'));
                        @endphp

                        <div class="mt-3" data-catatan-wadah @unless ($tampilCatatan) hidden @endunless>
                            <label for="catatan-pengajuan" class="block text-xs font-bold text-dark/60">
                                Catatan pendukung
                                <span class="text-[#c2414a]">(wajib)</span>
                            </label>

                            <textarea id="catatan-pengajuan" name="catatan_pengajuan" rows="2"
                                data-catatan-isian
                                @if ($tampilCatatan) required @endif
                                maxlength="500"
                                placeholder="Contoh: Bab 3 sudah saya tambahkan contoh kode dan latihan soal."
                                class="mt-1.5 w-full rounded-xl border border-lavender bg-white px-3.5 py-2.5 text-sm text-dark placeholder:text-dark/35">{{ old('catatan_pengajuan', $materi->catatan_pengajuan) }}</textarea>
                        </div>
                    @endif
                @endif

                {{-- Baris tombol --}}
                <div class="mt-4 flex shrink-0 flex-col gap-2.5 sm:flex-row sm:items-center">
                    @if ($modeEdit)
                        <a href="{{ route('user.karya-saya', ['tab' => 'materi']) }}" class="tombol-garis justify-center">
                            Batal
                        </a>

                        <button type="submit" class="tombol-utama justify-center">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.5 12.75 12 4.5l7.5 8.25M6 19.5h12a2.25 2.25 0 0 0 2.25-2.25V9.108a2.25 2.25 0 0 0-.659-1.591l-7.5-6.636a2.25 2.25 0 0 0-3.182 0l-7.5 6.636A2.25 2.25 0 0 0 4.5 9.108v8.142A2.25 2.25 0 0 0 6.75 19.5Z" />
                            </svg>

                            Simpan Perubahan
                        </button>

                        @if ($bolehDiajukan)
                            <button type="submit" name="publikasikan" value="1" data-tombol-publikasi
                                class="tombol-utama justify-center">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>

                                {{ $labelAjukan }}
                            </button>
                        @endif
                    @else
                        <button type="submit" name="publikasikan" value="0"
                            class="tombol-garis justify-center">
                            Simpan Draft
                        </button>

                        <button type="submit" name="publikasikan" value="1" data-tombol-publikasi
                            class="tombol-utama justify-center">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>

                            Ajukan Persetujuan
                        </button>
                    @endif
                </div>
            </div>
        </form>

        {{-- =========================
             DIALOG KONFIRMASI HAPUS BAB
        ========================== --}}
        <div class="dialog-bab" data-dialog-hapus role="dialog" aria-modal="true"
            aria-labelledby="judul-dialog-hapus">
            <div class="w-full max-w-sm rounded-2xl border border-ungu-line bg-white p-5 shadow-[0_30px_60px_-30px_rgba(49,46,129,0.8)]">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#fdecee] text-[#c2414a]"
                    aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </span>

                <h2 id="judul-dialog-hapus" class="mt-4 text-base font-extrabold text-dark">
                    Hapus bab ini?
                </h2>

                <p class="mt-1.5 text-sm leading-relaxed text-muted" data-dialog-pesan>
                    Bab yang dihapus tidak dapat dikembalikan.
                </p>

                <div class="mt-5 flex flex-col gap-2.5 sm:flex-row sm:justify-end">
                    <button type="button" data-dialog-batal class="tombol-garis justify-center">
                        Batal
                    </button>

                    <button type="button" data-dialog-hapus-tombol
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-[#d9535f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#c2414a]">
                        Ya, hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

