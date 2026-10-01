{{--
    Dua kartu aksi di kepala halaman "Konten Pembelajaran".

    Tautan dan kalimatnya ditulis di sini, bukan di view yang memakainya,
    supaya daftar di bawah dan form yang dibuka dari sini tidak pernah
    berbeda soal nama tombolnya.

    Ikon memakai ikon garis yang sudah dipakai di aplikasi (App\Support\Ikon),
    ditambah tanda plus di pojok ikon lewat .ad-aksi__ikon::after, jadi
    "tambah" terbaca sebelum teksnya.
--}}

<div {{ $attributes->class(['ad-aksi']) }}>
    <div class="ad-aksi__kartu">
        <span class="ad-aksi__ikon" aria-hidden="true">
            <x-admin.ikon nama="dokumen" ukuran="w-6 h-6" />
        </span>

        <div class="ad-aksi__isi">
            <h2 class="ad-aksi__judul">Tambah Materi</h2>

            <p class="ad-aksi__pesan">Buat materi pembelajaran untuk peserta didik.</p>

            <a href="{{ route('admin.konten.materi.tambah') }}" class="ad-tombol ad-tombol--utama ad-tombol--kecil ad-aksi__tombol">
                <x-admin.ikon nama="tambah" ukuran="w-4 h-4" :tebal="2.4" />

                Tambah Materi
            </a>
        </div>
    </div>

    <div class="ad-aksi__kartu">
        <span class="ad-aksi__ikon" aria-hidden="true">
            <x-admin.ikon nama="centang" ukuran="w-6 h-6" />
        </span>

        <div class="ad-aksi__isi">
            <h2 class="ad-aksi__judul">Tambah Quiz</h2>

            <p class="ad-aksi__pesan">Buat quiz untuk menguji pemahaman peserta didik.</p>

            <a href="{{ route('admin.konten.quiz.tambah') }}" class="ad-tombol ad-tombol--utama ad-tombol--kecil ad-aksi__tombol">
                <x-admin.ikon nama="tambah" ukuran="w-4 h-4" :tebal="2.4" />

                Tambah Quiz
            </a>
        </div>
    </div>
</div>
