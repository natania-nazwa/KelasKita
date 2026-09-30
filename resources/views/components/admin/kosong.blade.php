@props([
    'ikon' => 'kotak-masuk',
    'judul',
    'teks' => null,
])

{{--
    Empty state: kotak kosong dengan ikon bulat dan satu kalimat
    penjelasan.

    Dipakai di setiap daftar admin yang bisa benar-benar kosong, supaya
    halaman kosong tetap terlihat selesai, bukan seperti gagal dimuat.
--}}

<div {{ $attributes->class(['ad-kosong']) }}>
    <span class="ad-kosong__ikon">
        <x-admin.ikon :nama="$ikon" ukuran="w-6 h-6" />
    </span>

    <p class="ad-kosong__judul">{{ $judul }}</p>

    @if (filled($teks))
        <p class="ad-kosong__teks">{{ $teks }}</p>
    @endif
</div>
