{{--
    Banner sapaan di dashboard admin.

    Sengaja memakai kelas .ad-banner, bukan .ad-hero milik halaman Kelola
    Materi: halaman itu butuh banner tinggi dengan tombol besar, sedangkan
    banner dashboard dibuat pendek supaya analytics di bawahnya tidak
    terdorong jauh ke bawah. Kelas terpisah menjaga perubahan ini tidak
    menyentuh halaman lain.

    Isinya sengaja ditipis: badge "Platform Belajar Siswa" dan sepasang
    tombol di bawah teks dihapus supaya banner hanya jadi sapaan dan
    ilustrasi, tidak jadi blok navigasi yang menduplikasi sidebar dan
    kartu statistik di bawahnya.
--}}

@props([
    'nama' => 'Admin',
])

<section {{ $attributes->class(['ad-seksi', 'ad-banner']) }}>
    <div class="ad-banner__susun">
        <div class="ad-banner__teks">
            <h1 class="ad-banner__judul">Selamat datang, {{ $nama }} &#128075;</h1>

            <p class="ad-banner__sub">Kelola aktivitas belajar dan konten KelasKita</p>
        </div>

        <div class="ad-banner__ilustrasi">
            {{--
                Ilustrasi memakai berkas PNG (public/images/cover.png),
                bukan SVG yang digambar tangan. Berkasnya sudah punya
                latar transparan, palet soft purple, dan dekorasi sendiri
                (kartu kode, centang, topi wisuda), jadi tidak ada
                gelembung bicara tambahan yang perlu ditumpuk di atasnya.
            --}}
            <img src="{{ asset('images/cover.png') }}" width="828" height="552"
                alt="Ilustrasi seorang siswi sedang belajar memakai laptop di depan buku, tanaman, dan topi wisuda">
        </div>
    </div>
</section>
