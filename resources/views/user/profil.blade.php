@extends ('layouts.app')

@section ('title', 'Profil | KelasKita')

@section ('content')
    <div data-reveal class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-extrabold text-dark sm:text-3xl">Profil</h1>

            <p class="mt-1 text-dark/60">Kelola informasi akun dan preferensi kamu.</p>
        </div>

    </div>

    {{--
        Kabar berhasil dari simpan profil, hapus foto, atau ubah password
        dirender layout lewat komponen x-app.toast: kartu pojok kanan bawah
        yang menutup sendiri setelah lima detik dan punya tombol tutup,
        sama seperti halaman materi, quiz, karya saya, jadwal, dan
        masukan-saran (lihat resources/views/layouts/app.blade.php).
    --}}

    @include ('user.partials.profil.kepala')

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        @include ('user.partials.profil.akun')

        @include ('user.partials.profil.keamanan')
    </div>

    @include ('user.partials.profil.tampilan')

    @include ('user.partials.profil.bahaya')

    {{-- =========================
         DIALOG
    ==========================
         Keempat dialog halaman ini dirender di sini, di luar kartu mana
         pun, supaya posisinya tidak ikut bergeser mengikuti grid dan
         tidak pernah terpotong overflow kartu.

         Tidak ada toast di halaman ini lagi: toast itu hanya dipakai untuk
         memberi tahu bahwa hapus akun belum punya endpoint, dan sekarang
         endpoint-nya sudah ada -- errornya muncul di dalam dialog yang
         memang sedang dibuka. --}}
    <x-profil.edit :pengguna="$pengguna" />

    <x-profil.kata-sandi />

    <x-profil.hapus-foto />

    <x-profil.hapus-akun />
@endsection
