@props([
    'status',
    'label',
])

@php
    /*
     * Warna lunak per status, dengan ikon kecil di dalam lencana (bukan
     * hanya teks atau titik). Warna teksnya memakai nilai yang sudah dipakai
     * design system admin untuk status serupa di halaman verifikasi, supaya
     * satu materi terlihat sama di mana pun.
     */
    $gaya = match ($status) {
        'published' => ['pita' => 'bg-[var(--ad-cucian-sukses)] text-[var(--ad-teks-sukses)]', 'ikon' => 'tanda-centang'],
        'pending' => ['pita' => 'bg-[var(--ad-cucian-peringatan)] text-[var(--ad-teks-peringatan)]', 'ikon' => 'jam'],
        'rejected' => ['pita' => 'bg-[var(--ad-cucian-bahaya)] text-[var(--ad-teks-bahaya)]', 'ikon' => 'silang-polos'],
        default => ['pita' => 'bg-[var(--ad-permukaan-lavender)] text-[var(--ad-teks-3)]', 'ikon' => 'pena'],
    };
@endphp

<span
    {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold whitespace-nowrap', $gaya['pita']]) }}>
    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path($gaya['ikon']) }}" />
    </svg>
    {{ $label }}
</span>