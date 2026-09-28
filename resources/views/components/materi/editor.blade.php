{{--
    Panel tengah halaman "Tambah Materi" / "Edit Materi": editor isi materi.

    Area tulisnya adalah contenteditable biasa (resources/js/materi-tambah.js),
    jadi tidak perlu library tambahan. Toolbar memakai execCommand dan
    selalu mengikuti bab yang sedang aktif.

    $isi dipakai sebagai isi awal saat mengedit (mode tambah = null).
    Depois JavaScript aktif, isinya digantikan isi bab yang dipilih dari
    field "bab".
--}}
@props([
    'isi' => null,
])
<section {{ $attributes->class(['kartu-form overflow-hidden']) }}>
    <header class="kartu-form__kepala">
        <span class="kartu-form__ikon" aria-hidden="true">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
        </span>

        <h2 class="kartu-form__judul">Isi Materi</h2>

        <span class="ml-auto hidden text-[11px] font-semibold text-muted sm:block">
            Format teks mendukung judul, daftar, kutipan, dan gambar.
        </span>
    </header>

    {{-- Kepala editor: nomor bab aktif + judul bab --}}
    <div class="flex items-center gap-3 border-b border-[#f2effe] bg-ungu-bg px-4 py-3 sm:px-5">
        <span data-editor-bab
            class="shrink-0 rounded-lg bg-ungu px-2.5 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.1em] text-white shadow-[0_10px_20px_-16px_rgba(124,77,255,1)]">
            Bab 1
        </span>

        <div class="min-w-0 flex-1">
            <label for="judul-bab" class="sr-only">Judul bab</label>

            <input type="text" id="judul-bab" data-editor-judul maxlength="120" placeholder="Judul Bab"
                class="w-full border-0 bg-transparent p-0 text-sm font-bold text-dark outline-none placeholder:font-medium placeholder:text-muted/70 focus:ring-0">
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="alat-editor" role="toolbar" aria-label="Format teks">
        <button type="button" class="alat-tombol" data-cmd="bold" title="Tebal (Ctrl+B)" aria-label="Tebal">
            <span class="font-serif text-sm font-extrabold">B</span>
        </button>

        <button type="button" class="alat-tombol" data-cmd="italic" title="Miring (Ctrl+I)" aria-label="Miring">
            <span class="font-serif text-sm font-bold italic">I</span>
        </button>

        <button type="button" class="alat-tombol" data-cmd="underline" title="Garis bawah (Ctrl+U)" aria-label="Garis bawah">
            <span class="text-sm font-bold underline">U</span>
        </button>

        <span class="alat-pemisah" aria-hidden="true"></span>

        <label for="format-bab" class="sr-only">Jenis paragraf</label>
        <select id="format-bab" class="alat-pilih" data-cmd-format title="Heading">
            <option value="p">Paragraf</option>
            <option value="h2">Heading 2</option>
            <option value="h3">Heading 3</option>
        </select>

        <span class="alat-pemisah" aria-hidden="true"></span>

        <button type="button" class="alat-tombol" data-cmd="insertUnorderedList" title="Daftar berbutir" aria-label="Daftar berbutir">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
        </button>

        <button type="button" class="alat-tombol" data-cmd="insertOrderedList" title="Daftar bernomor" aria-label="Daftar bernomor">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12M3.75 6.75V4.875c0-.621.504-1.125 1.125-1.125H9.75c.621 0 1.125.504 1.125 1.125V6.75M3.75 6.75v.008c0 .621.504 1.125 1.125 1.125H9.75c.621 0 1.125-.504 1.125-1.125V6.75m0 9v.008c0 .621.504 1.125 1.125 1.125h4.875c.621 0 1.125-.504 1.125-1.125V15.75m0 0h1.125c.621 0 1.125-.504 1.125-1.125V13.5m0 0V12.375c0-.621-.504-1.125-1.125-1.125H12.75c-.621 0-1.125.504-1.125 1.125v1.125" />
            </svg>
        </button>

        <button type="button" class="alat-tombol" data-cmd="quote" title="Kutipan" aria-label="Kutipan">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
            </svg>
        </button>

        <button type="button" class="alat-tombol" data-cmd="link" title="Tautan" aria-label="Tautan">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m9.394-3.634a4.5 4.5 0 0 0-1.242-7.244l-4.5-4.5a4.5 4.5 0 0 0-6.364 6.364L4.508 8.964m9.394 3.634 1.757-1.757m-1.242-7.244 4.5 4.5a4.5 4.5 0 0 1 0 6.364l-4.5 4.5" />
            </svg>
        </button>

        <button type="button" class="alat-tombol" data-tambah-gambar title="Sisipkan gambar" aria-label="Sisipkan gambar">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
            </svg>
        </button>

        <span class="alat-pemisah" aria-hidden="true"></span>

        <button type="button" class="alat-tombol" data-cmd="justifyLeft" title="Rata kiri" aria-label="Rata kiri">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h10.5m-10.5 5.25h16.5" />
            </svg>
        </button>

        <button type="button" class="alat-tombol" data-cmd="justifyCenter" title="Rata tengah" aria-label="Rata tengah">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
        </button>

        <button type="button" class="alat-tombol" data-cmd="justifyRight" title="Rata kanan" aria-label="Rata kanan">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M10.5 12h16.5M3.75 17.25h16.5" />
            </svg>
        </button>

        <button type="button" class="tombol-utama ml-auto hidden shrink-0 px-3.5 py-1.5 text-xs sm:inline-flex"
            data-tambah-gambar>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>

            Tambah Gambar
        </button>
    </div>

    {{-- Area tulis --}}
    <div class="kertas-editor border-b border-[#f2effe]" contenteditable="true" role="textbox"
        aria-multiline="true" aria-label="Isi materi" spellcheck="true" data-editor
        data-placeholder="Tulis isi materi di sini...">{{ old('isi', $isi) ? trim(nl2br(e(old('isi', $isi)))) : '' }}</div>

    {{-- Footer editor: penghitung karakter --}}
    <div class="border-t border-[#f2effe] px-4 py-3 sm:px-5">
        <span class="text-xs font-semibold tabular-nums text-muted" data-editor-huruf>0 karakter</span>
    </div>

    <input type="file" accept="image/*" multiple class="sr-only" data-gambar-input>
</section>
