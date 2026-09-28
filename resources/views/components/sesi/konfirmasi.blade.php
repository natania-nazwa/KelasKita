@props([
    'judul' => 'Lanjutkan?',
    'pesan' => null,
    'tombol' => 'Ya, Lanjutkan',
    'tone' => 'primary',
])

@php
    /*
     * Dialog konfirmasi untuk aksi sesi quiz (Mulai Quiz, Akhiri Quiz,
     * Selesai).
     *
     * Beda dari x-app.konfirmasi yang khusus hapus karya: dialog ini
     * sekaligus bisa berarti "lanjutkan" maupun "batalkan", jadi warna
     * tombol dan ikonnya ikut berubah lewat $tone.
     *
     * Satu halaman bisa punya lebih dari satu dialog kalau ada dua aksi
     * dengan tones berbeda (mis. "Mulai" ungu dan "Akhiri" merah).
     * resources/js/quiz-lobby.js memilih dialog yang cocok dari atribut
     * data-lobi-konfirmasi-tone pada form pemanggilnya.
     *
     * Styling memakai .dialog-bab yang sama dengan dialog hapus bab dan
     * dialog hapus karya, jadi tidak ada gaya dialog baru di app.css.
     *
     * Pemanggilnya di resources/js/quiz-lobby.js membaca judul dan pesan
     * dari atribut data-* pada form atau tombol pemanggil, jadi teksnya
     * bisa berbeda per tempat tanpa dialog ikut diduplikasi.
     */
    $merah = $tone === 'henti';
@endphp

<div class="dialog-bab" data-lobi-dialog data-lobi-dialog-tone="{{ $merah ? 'henti' : 'primary' }}"
    role="dialog" aria-modal="true"
    aria-labelledby="judul-dialog-lobi" aria-describedby="pesan-dialog-lobi">
    <div class="w-full max-w-sm rounded-2xl border border-ungu-line bg-white p-5 shadow-[0_30px_60px_-30px_rgba(49,46,129,0.8)]">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $merah ? 'bg-[#fdecee] text-[#c2414a]' : 'bg-ungu-bg text-primary' }}"
            aria-hidden="true">
            @if ($merah)
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('silang') }}" />
                </svg>
            @else
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kode') }}" />
                </svg>
            @endif
        </span>

        <h2 id="judul-dialog-lobi-{{ $merah ? 'henti' : 'primary' }}" class="mt-4 text-base font-extrabold text-dark" data-lobi-dialog-judul>
            {{ $judul }}
        </h2>

        <p id="pesan-dialog-lobi-{{ $merah ? 'henti' : 'primary' }}" class="mt-1.5 text-sm leading-relaxed text-muted" data-lobi-dialog-pesan>
            {{ $pesan ?? 'Pastikan kamu sudah siap.' }}
        </p>

        <div class="mt-5 flex flex-col gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" data-lobi-dialog-batal class="tombol-garis justify-center">
                Batal
            </button>

            <button type="button" data-lobi-dialog-ya
                class="inline-flex items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold text-white transition {{ $merah ? 'bg-[#d9535f] hover:bg-[#c2414a]' : 'bg-primary hover:bg-primary-dark' }}">
                <span data-lobi-dialog-tombol>{{ $tombol }}</span>
            </button>
        </div>
    </div>
</div>
