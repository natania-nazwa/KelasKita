@props([
    // "materi" atau "quiz": menentukan ikon, warna, dan di halaman mana
    // tombol "Tinjau" akan membuka.
    'jenis',
    'judul',
    'pembuat',
    'kategori' => null,
    'kategoriWarna' => null,
    'kategoriIkon' => null,
    'tanggal' => null,
    'rincian' => null,
    'status' => 'pending',
    'statusLabel' => 'Menunggu Verifikasi',
    'tautan' => '#',
])

{{--
    Satu baris di daftar "Perlu Ditinjau".

    Ikon dan warna kotak diambil dari kategori aslinya lewat
    Pelajaran::warna(), supaya warna di sini sama persis dengan warna
    kategori di halaman Materi dan Quiz milik pengguna. Tidak ada
    palet kedua untuk satu makna yang sama.
--}}

@php
    $warna = $kategoriWarna ?: '#6D4AFF';
    $huruf = $kategoriIkon ?: ($jenis === 'quiz' ? 'Q' : 'M');
@endphp

<article {{ $attributes->class(['ad-tinjau__item']) }}>
    <span class="ad-tinjau__ikon" style="background-color: {{ $warna }};" aria-hidden="true">
        {{ $huruf }}
    </span>

    <div class="ad-tinjau__isi">
        <p class="ad-tinjau__judul">
            {{ $jenis === 'quiz' ? 'Quiz' : 'Materi' }}: {{ $judul }}
        </p>

        <p class="ad-tinjau__meta">
            <span>
                Dibuat oleh <strong>{{ $pembuat }}</strong>
            </span>

            @if (filled($kategori))
                <span>{{ $kategori }}</span>
            @endif

            @if (filled($rincian))
                <span>{{ $rincian }}</span>
            @endif

            @if (filled($tanggal))
                <span>{{ $tanggal }}</span>
            @endif
        </p>
    </div>

    <div class="ad-tinjau__kanan">
        <x-admin.lencana :status="$status" :label="$statusLabel" />

        <a href="{{ $tautan }}" class="ad-tombol ad-tombol--utama ad-tombol--kecil">
            Tinjau
            <x-admin.ikon nama="panah-kanan" ukuran="w-3.5 h-3.5" />
        </a>
    </div>
</article>
