@props([
    /**
     * Deret pelajaran terlaris, bentuknya sama dengan keluaran
     * StatistikAdmin::pelajaranTerpopuler():
     * ['nama' => 'PPLG', 'nilai' => 24, 'persentase' => 28, 'warna' => '#6D4AFF'].
     *
     * Kalau kosong, kartu menampilkan empty state, bukan lingkaran kosong
     * yang menyesatkan.
     */
    'data' => [],
])

{{--
    Kartu analytics "Pelajaran yang Disukai": donat chart + legenda.

    Chart-nya memakai x-admin.pie yang sudah ada: cincin digambar sebagai
    lingkaran dengan stroke tebal, jadi bentuknya sudah donat dan angka
    total berada di tengah. Tidak ada pustaka chart yang ditambahkan, dan
    tidak ada duplikasi perhitungan busur.

    Keterangan sumber datanya diletakkan di kaki card, bukan di kepala,
    supaya kepala card tidak jadi dua baris teks.

    Card ini TIDAK memakai class untuk mencegah peregangannya. Kedua kartu
    analytics harus sama tinggi, jadi grid dibiarkan meregangkannya
    (align-items: stretch bawaan grid). Kalau card ini diberi align-self,
    tinggi card kiri jadi ikut isinya saja sementara card kanan tetap
    diregangkan, dan tepi bawah keduanya jadi tidak rata.
--}}

{{--
    Section ini SENGAJA tidak memakai kelas .ad-seksi.

    .ad-seksi tidak punya deklarasi sendiri; satu-satunya aturannya adalah
    .ad-seksi + .ad-seksi { margin-top }, yang ada untuk menjeda antara
    section halaman yang disusun vertikal. Dua kartu analytics ini adalah
    item grid yang berdampingan, dan keduanya sibling bersebelahan -- jadi
    kalau keduanya memakai .ad-seksi, rule itu menyala pada kartu kedua
    dan memberinya margin-top 1,75rem. Akibatnya tepi atas kartu kanan
    turun 28px dan keduanya terlihat tidak rata, padahal grid-nya sudah
    benar.

    .ad-seksi juga tidak diperlukan di sini: jarak ke section di atasnya
    datang dari .ad-banner + .ad-grid--analitik, dan ke section di
    bawahnya dari .ad-seksi + .ad-seksi pada section pembungkus-nya.
--}}

<section {{ $attributes->class(['ad-kartu', 'ad-analitik']) }}>
    <header class="ad-kartu__kepala">
        <div class="ad-kartu__kepala-titik">
            {{-- Warna chip ikut isi kartunya: hati ungu untuk "disukai",
                 naik hijau untuk "login", supaya dua kartu analytics tidak
                 terlihat sama persis. --}}
            <span class="ad-cepat__ikon ad-cepat__ikon--pink" aria-hidden="true">
                <x-admin.ikon nama="hati" ukuran="w-5 h-5" />
            </span>

            <h2 class="ad-kartu__kepala-judul">Pelajaran yang Disukai</h2>
        </div>
    </header>

    @if ($data === [])
        <div class="ad-kartu__badan">
            <x-admin.kosong ikon="hati" judul="Belum ada data aktivitas pembelajaran"
                teks="Pelajaran yang paling sering dibuka akan muncul di sini setelah siswa mulai belajar." />
        </div>
    @else
        <div class="ad-kartu__badan">
            {{-- Legenda tetap di sebelah kanan donat (donat kiri, teks
                 kanan). Yang diubah cuma perataannya: teksnya menempel di
                 atas, bukan mengambang di tengah-tengah. --}}
            <x-admin.pie :data="$data" label-nilai="Aktivitas" />
        </div>

        <footer class="ad-kartu__kaki ad-kartu__kaki--total">
            <p class="ad-kartu__catatan">
                Data berdasarkan aktivitas pembelajaran pengguna
            </p>
        </footer>
    @endif
</section>
