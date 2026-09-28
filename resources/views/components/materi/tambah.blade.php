@props([
    'label' => 'Tambah Materi',
    'href' => null,
])

{{--
    Tombol "Tambah Materi". Kalau $href tidak diisi, mengarah ke form
    tambah materi milik pengguna sendiri.
--}}

<a href="{{ $href ?? route('user.materi.tambah') }}"
    {{ $attributes->class([
        'tombol-materi inline-flex items-center justify-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white',
    ]) }}>
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>

    {{ $label }}
</a>
