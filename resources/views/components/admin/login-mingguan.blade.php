@props([
    /**
     * Deret jumlah login per minggu, bentuknya sama dengan keluaran
     * StatistikAdmin::loginMingguan():
     * ['label' => '1–7', 'bulan' => 'Sep', 'rentang' => '1–7 Sep 2026', 'nilai' => 12].
     *
     * "label" dan "bulan" adalah dua baris sumbu X supaya label minggu
     * yang panjang tidak menabrak label sebelahnya; "rentang" dipakai
     * untuk teks tooltip dan pembaca layar.
     */
    'data' => [],
])

{{--
    Kartu analytics "Aktivitas Login Mingguan": grafik garis.

    Angkanya dihitung di server dari tabel tb_riwayat_login (satu baris
    per login berhasil), jadi kartu ini tidak pernah menampilkan angka
    contoh. Jumlah yang dihitung adalah JUMLAH login, bukan jumlah orang.

    Kartu ini selalu menampilkan jumlah minggu yang diminta statistik
    (StatistikAdmin::mingguLogin(), enam minggu) termasuk minggu yang
    sedang berjalan, walau sebagian besarnya bernilai nol. Bulannya tetap
    digambar, bukan disembunyikan, supaya terbaca sebagai "belum ada login
    di minggu itu" dan bukan sebagai minggu yang tidak punya datanya.

    Jumlah minggu diambil dari jumlah baris data, bukan ditulis "6" di
    markup, supaya kartu ini tidak berbohong kalau rentangnya diubah
    di StatisticAdmin.
--}}

@php
    $jumlahMinggu = count($data);
@endphp

{{--
    Section ini SENGAJA tidak memakai kelas .ad-seksi, sama seperti
    "Pelajaran yang Disukai".

    Alasannya ada di comments section itu: .ad-seksi hanya punya aturan
    .ad-seksi + .ad-seksi { margin-top }, dan dua kartu ini sibling
    bersebelahan di dalam grid. Kalau keduanya memakai .ad-seksi, kartu
    kedua dapat margin-top 1,75rem dan tepi atasnya turun 28px.
--}}

<section {{ $attributes->class(['ad-kartu', 'ad-analitik']) }}>
    <header class="ad-kartu__kepala">
        <div class="ad-kartu__kepala-titik">
            <span class="ad-cepat__ikon ad-cepat__ikon--hijau" aria-hidden="true">
                <x-admin.ikon nama="naik" ukuran="w-5 h-5" />
            </span>

            <div class="min-w-0">
                <h2 class="ad-kartu__kepala-judul">Aktivitas Login Mingguan</h2>

                <p class="ad-kartu__subjudul">Jumlah login pengguna berdasarkan minggu</p>
            </div>
        </div>
    </header>

    <div class="ad-kartu__badan">
        {{--
            tinggi 215 memberi ruang untuk dua baris label minggu tanpa
            menekan garis ke atas, dan lebarTitik 96 memberi jarak antar
            label yang lega. Tanpa itu, enam label saling berdesakan.
        --}}
        <x-admin.grafik-garis :data="$data" :tinggi="215" :lebar-titik="96" label-nilai="login" />
    </div>

    <footer class="ad-kartu__kaki ad-kartu__kaki--total">
        <p class="ad-kartu__catatan">
            {{-- Judul kalimatnya tetap "N minggu terakhir" walau belum ada
                 satu pun login, karena rentang minggunyalah yang sedang
                 dilaporkan, bukan jumlahnya. --}}
            Total login dalam {{ $jumlahMinggu }} minggu terakhir
        </p>

        <p class="ad-kartu__catatan-angka">{{ array_sum(array_column($data, 'nilai')) }}</p>
    </footer>
</section>
