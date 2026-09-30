@props([
    'publik' => true,
])

{{--
    Lencana mode quiz.

    Quiz publik tayang untuk semua pengguna, jadi harus disetujui admin
    dulu. Quiz mode kode tidak pernah masuk daftar verifikasi: yang
    berbasis kode hanya miliknya sendiri, jadi lencana ini sengaja
    tidak pernah menampilkan status "menunggu".
--}}

<span {{ $attributes->class(['ad-lencana', $publik ? 'ad-lencana--info' : 'ad-lencana--abu']) }}>
    @if ($publik)
        <x-admin.ikon nama="dunia" ukuran="w-3 h-3" />
    @else
        <x-admin.ikon nama="gembok" ukuran="w-3 h-3" />
    @endif

    {{ $publik ? 'PUBLIC' : 'PRIVATE / CODE' }}
</span>
