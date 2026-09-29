{{--
    Pengelola bab sekaligus wadah preview. Materi bisa dibagi jadi
    beberapa bab tanpa pindah halaman: chip di sini sekaligus menjadi
    tab yang memilih bab aktif pada editor dan preview.

    Kepala kartu berisi dua tab — "Daftar Bab" dan "Preview" — supaya
    pratinjau tidak perlu jadi kartu terpisah yang panjang.

    Daftar bab dirender oleh JavaScript (resources/js/materi-tambah.js),
    komponen ini hanya menyediakan kerangka + tombol tambah.
--}}
@props([
    // Hanya diteruskan ke tab Preview, yang butuh nilai yang sama dengan
    // form supaya kepala pratinjau dimulai dari isian yang sudah ada.
    'kategori' => [],
    'materi' => null,
])

<section {{ $attributes->class(['kartu-form']) }}>
    <header class="kartu-form__kepala flex-wrap gap-3">
        <span class="kartu-form__ikon" aria-hidden="true">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
            </svg>
        </span>

        <div class="tab-unsur" role="tablist" aria-label="Tampilan kartu bab">
            <button type="button" role="tab" id="tab-daftar" aria-controls="panel-daftar"
                aria-selected="true" class="tab-unsur__tombol is-aktif" data-tab-bab>
                Daftar Bab
            </button>

            <button type="button" role="tab" id="tab-preview" aria-controls="panel-preview"
                aria-selected="false" class="tab-unsur__tombol" data-tab-preview>
                Preview
            </button>
        </div>

        <button type="button" data-bab-tambah-utama
            class="tombol-utama ml-auto shrink-0 px-4 py-2 text-xs">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>

            <span data-bab-tambah-label>+ Tambah Bab</span>
        </button>
    </header>

    <div class="kartu-form__badan">
        {{-- Panel: daftar bab --}}
        <div id="panel-daftar" role="tabpanel" aria-labelledby="tab-daftar" data-panel-bab>
            <div class="bab-baris" data-bab-list></div>

            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                <p class="text-[11px] leading-relaxed text-muted">
                    Klik bab untuk mengedit isinya. Gunakan tombol <span class="font-bold text-ungu">⋮</span>
                    untuk mengganti nama, menduplikat, mengubah urutan, atau menghapus bab.
                </p>

                <span data-bab-jumlah
                    class="shrink-0 rounded-full bg-ungu-bg px-2.5 py-1 text-[11px] font-extrabold text-ungu">1 Bab</span>
            </div>
        </div>

        {{-- Panel: preview materi --}}
        <div id="panel-preview" role="tabpanel" aria-labelledby="tab-preview" class="hidden"
            data-panel-preview>
            <x-materi.preview :kategori="$kategori" :materi="$materi" />
        </div>
    </div>
</section>
