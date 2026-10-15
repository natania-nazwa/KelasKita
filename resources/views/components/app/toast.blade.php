@props([
    // Isi pesan. Null atau kosong berarti tidak ada yang perlu
    // ditampilkan, jadi komponen ini tidak menghasilkan markup.
    'pesan' => null,

    // "sukses" (bawaan) untuk kabar hasil aksi yang berhasil, "galat"
    // untuk pesan yang gagal — isian belum lengkap, aksi ditolak, dan
    // sejenisnya. Menentukan warna ikon dan tepi kartunya.
    'jenis' => 'sukses',
])

@php
    $galat = $jenis === 'galat';
@endphp

{{--
    Toast hasil aksi di area pengguna: "Materi "X" tersimpan dan menunggu
    persetujuan admin.", "Quiz "Y" berhasil dihapus.", dan sejenisnya.

    Bentuk dan perilakunya sengaja sama dengan toast di area admin
    (components/admin/toast.blade.php) — kartu kecil di pojok kanan bawah,
    menutup dirinya sendiri setelah lima detik, dan selalu punya tombol
    tutup. Yang berbeda hanya warnanya, karena dua area tidak berbagi
    token warna.

    Sumbernya selalu flash session "sukses" (atau "galat"), jadi toast ini
    hanya muncul setelah aksi yang benar-benar baru saja terjadi — bukan
    tiap kali halaman dibuka.

    Letak dan atributnya (data-toast / data-toast-tutup) dibaca modul
    toast di resources/js/app.js. Tombol tutup ada di markup, bukan
    dibuat oleh JavaScript, jadi pesannya tetap bisa ditutup walau
    JavaScript tidak berjalan.
--}}
@if (filled($pesan))
    <div {{ $attributes->class([
        'app-toast',
        'app-toast--galat' => $galat,
        'bg-white',
        'border-lavender' => ! $galat,
        'border-[#f3c9cb]' => $galat,
        'text-dark' => ! $galat,
        'text-[#a8323c]' => $galat,
        'shadow-[0_2px_4px_rgba(33,26,58,0.05),0_26px_46px_-28px_rgba(76,47,179,0.32)]',
    ]) }} data-toast role="status" aria-live="polite">
        <span class="app-toast__ikon" aria-hidden="true">
            @if ($galat)
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
            @else
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            @endif
        </span>

        <p class="app-toast__teks">{{ $pesan }}</p>

        <button type="button" class="app-toast__tutup" data-toast-tutup aria-label="Tutup pesan">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
@endif