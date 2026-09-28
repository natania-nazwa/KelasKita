@props([
    // Tab status yang sedang aktif: null = semua.
    'statusAktif' => null,
    // Jumlah tiap status, sudah memperhitungkan kata kunci pencarian.
    'jumlahStatus' => [],
    // Pilihan urutan untuk dropdown sorting.
    'daftarUrutan' => [],
    'urutAktif' => 'terbaru',
    'kataKunci' => '',
    // Route tujuan form, ikut berbeda kalau sedang melihat satu quiz.
    'aksi' => 'user.hasil',
    'param' => [],
])

{{--
    Tab filter status + kolom pencarian + dropdown sorting, semuanya
    dalam satu form GET supaya hasil pencarian tetap punya URL yang bisa
    disalin atau di-bookmark.

    Tab, pencarian, dan sorting saling bertahan: ganti tab atau ganti
    urutan tidak menghapus kata kunci, dan sebaliknya.

    Angka di dalam tab berasal dari $jumlahStatus, yang dihitung ulang
    di server mengikuti kata kunci yang sedang dipakai.
--}}

@php
    $url = function (array $ubah = []) use ($aksi, $param, $statusAktif, $urutAktif, $kataKunci) {
        return route($aksi, array_merge(
            ['status' => $statusAktif, 'urut' => $urutAktif, 'q' => $kataKunci],
            $param,
            $ubah,
        ));
    };

    $tab = [
        ['nilai' => null, 'label' => 'Semua', 'jumlah' => $jumlahStatus['semua'] ?? 0],
        ['nilai' => 'selesai', 'label' => 'Selesai', 'jumlah' => $jumlahStatus['selesai'] ?? 0],
        ['nilai' => 'proses', 'label' => 'Dalam Proses', 'jumlah' => $jumlahStatus['proses'] ?? 0],
        ['nilai' => 'gagal', 'label' => 'Gagal', 'jumlah' => $jumlahStatus['gagal'] ?? 0],
    ];
@endphp

{{--
    Susunan: tab di kiri, cari + sorting di kanan. xl:flex-row supaya
    ketiganya muat side-by-side di layar besar; di bawah itu membungkus
    ke bawah dan kolom cari melebar penuh, jadi tidak ada scroll
    horizontal di ponsel.
--}}
<div {{ $attributes->class(['flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between']) }}>

    {{-- Tab: aktif = ungu solid + teks putih, tidak aktif = lavender. --}}
    <div class="tab-karya" role="tablist" aria-label="Saring hasil quiz">
        @foreach ($tab as $item)
            @php $aktif = $statusAktif === $item['nilai']; @endphp

            <a href="{{ $url(['status' => $item['nilai']]) }}" role="tab" aria-selected="{{ $aktif ? 'true' : 'false' }}"
                @class(['tab-karya__item', 'tab-karya__item--aktif' => $aktif])>
                {{ $item['label'] }}

                <span class="tab-karya__jumlah">{{ $item['jumlah'] }}</span>
            </a>
        @endforeach
    </div>

    <form action="{{ route($aksi, $param) }}" method="GET" data-cari-form
        class="flex flex-col gap-3 sm:flex-row sm:items-center min-w-0 flex-1">

        {{-- Tab dan urutan ikut dibawa sebagai hidden, supaya keduanya
             tidak hilang ketika kolom cari dikirim. --}}
        @if ($statusAktif !== null)
            <input type="hidden" name="status" value="{{ $statusAktif }}">
        @endif

        @if ($urutAktif !== 'terbaru')
            <input type="hidden" name="urut" value="{{ $urutAktif }}">
        @endif

        <div class="relative min-w-0 flex-1">
            <span
                class="pointer-events-none absolute left-4 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white text-dark/40 shadow-[0_2px_8px_rgba(33,26,58,0.06)]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>

            <input type="search" name="q" value="{{ $kataKunci }}" data-cari-input autocomplete="off"
                placeholder="Cari quiz..." aria-label="Cari hasil quiz"
                class="kolom-cari w-full pl-14 {{ $kataKunci !== '' ? 'pr-12' : 'pr-4' }}">

            @if ($kataKunci !== '')
                <a href="{{ $url(['q' => null]) }}"
                    class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-dark/35 transition hover:bg-brand-bg hover:text-dark"
                    aria-label="Hapus kata kunci">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </a>
            @endif
        </div>

        {{-- Dropdown sorting, duduk di dalam form yang sama supaya keduanya
             tetap terkirim bareng. Berubah langsung submit (dikerjakan oleh
             initCari() di resources/js/app.js). --}}
        <div class="relative min-w-0 sm:w-48">
            <span class="pointer-events-none absolute left-4 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-dark/35"
                aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5h12m0 0-3-3m3 3-3 3m-9 9h12m0 0-3 3m3-3-3-3M6 12h.008v.008H6V12Z" />
                </svg>
            </span>

            <select name="urut" data-cari-filter class="pilih-kategori" aria-label="Urutkan hasil">
                @foreach ($daftarUrutan as $pilihan)
                    <option value="{{ $pilihan['nilai'] }}" @selected($urutAktif === $pilihan['nilai'])>
                        {{ $pilihan['label'] }}
                    </option>
                @endforeach
            </select>

            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-dark/35" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </span>
        </div>
    </form>
</div>
