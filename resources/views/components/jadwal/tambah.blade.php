@props([
    // Tanggal yang sedang dibuka di halaman jadwal, supaya tombol ini
    // membuka form untuk hari yang sama. Null = hari ini.
    'tanggal' => null,
    'label' => 'Tambah Jadwal',
])

{{--
    Tombol "Tambah Jadwal". Kalau $tanggal diisi, ?tanggal ikut dibawa
    supaya form yang terbuka sudah terisi hari yang sedang dilihat.
--}}

<a href="{{ route('user.jadwal.tambah', array_filter(['tanggal' => $tanggal?->toDateString()], fn ($nilai) => filled($nilai))) }}"
    {{ $attributes->class([
        'tombol-utama shrink-0 text-xs',
    ]) }}>
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>

    {{ $label }}
</a>
