{{--
    Isi materi di area admin: Daftar Isi + kartu seksi.

    Dipakai dua kali, dan itu sebabnya ia dipisah jadi view sendiri:

      - resources/views/admin/materi-detail.blade.php, untuk materi yang
        sudah tersimpan;
      - endpoint pratinjau pada form Tambah/Edit Materi, untuk materi yang
        sedang disusun (lihat Admin\KontenMateriController::pratinjau).

    Keduanya sengaja memakai komponen yang sama dengan halaman detail milik
    pengguna (x-materi.detail-daftar-isi dan .detail-konten) dan pemecah yang
    sama (App\Support\DetailMateri), jadi apa yang dibaca admin di pratinjau
    persis sama dengan yang akan dibaca pembaca setelah materi disimpan.

    Yang menentukan tampilan di sini hanya struktur grid: proporsi 27 : 73,
    min-w-0 di kedua kolom supaya blok kode yang lebar tidak mendorong
    halaman, dan Daftar Isi baru muncul kalau materi punya lebih dari satu
    seksi. Wrapper .ad-seksi dipakai halaman penuh sebagai pemanggilnya,
    sedangkan fragment pratinjau membungkus sendiri. Yang penting di sini
    hanya satu definisi, supaya pratinjau dan halaman detail tidak bisa
    berbeda karena pemanggilnya berbeda.

    Navigasi antar bab milik detail-konten ikut dirender. Di halaman detail
    ia diisi resources/js/materi-detail.js; di pratinjau, karena markup-nya
    datang dari server setiap kali isian berubah, yang mengisinya adalah
    resources/js/materi-tambah.js.
--}}

@if (count($detail['seksi']) > 1)
    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,27fr)_minmax(0,73fr)]">

        <div class="min-w-0">
            <x-materi.detail-daftar-isi :seksi="$detail['seksi']" />
        </div>

        <div class="min-w-0">
            <x-materi.detail-konten :seksi="$detail['seksi']" :detail="$detail" />
        </div>
    </div>
@else
    <div class="min-w-0">
        <x-materi.detail-konten :seksi="$detail['seksi']" :detail="$detail" />
    </div>
@endif