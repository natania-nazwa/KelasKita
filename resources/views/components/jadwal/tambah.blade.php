@props([
    'label' => 'Tambah Jadwal',
])

{{--
    Tombol "Tambah Jadwal". Form yang terbuka selalu kosong — hari dan jam
    dipilih sendiri di form, bukan diwarisi dari tanggal yang sedang dibuka.
--}}

<a href="{{ route('user.jadwal.tambah') }}"
    {{ $attributes->class([
        'tombol-utama shrink-0 text-xs',
    ]) }}>
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>

    {{ $label }}
</a>
