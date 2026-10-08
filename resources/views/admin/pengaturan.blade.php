@extends('layouts.admin')

@section('title', 'Pengaturan | KelasKita')

@section('content')

    {{--
        Halaman "Pengaturan" area admin: pusat semua pengaturan admin.

        Layout dua kolom mengikuti pola yang dipakai halaman admin lain:
        .ad-atur-kolom berubah jadi satu kolom di bawah 1024px. Semua isi
        kartu adalah baris pengaturan, tidak ada satu pun form yang disimpan
        di halaman ini, sehingga tinggi tiap kartu mengikuti isinya dan tidak
        ada kartu besar yang memenuhi layar.

        Yang dipisah halaman dan yang dipisah dialog mengikuti beratnya
        pekerjaan, bukan selera:

          Halaman  : Profil, Keamanan, Kelola Mata Pelajaran, Sesi Login,
                     Informasi Sistem, Tentang. Semuanya punya langkah lebih
                     dari satu atau daftar yang panjang.
          Dialog   : Notifikasi dan Publikasi. Semuanya satu form pendek.
          Inline   : Tampilan. Kendalinya sudah ada di barisnya, jadi
                     membuka halaman hanya untuk satu tombol tidak masuk akal.

        Baris yang membuka dialog ditulis langsung di sini sebagai <button>
        dengan kelas ad-atur-baris, bukan lewat x-admin.atur-baris: komponen
        itu merender <a>, dan tombolnya harus benar-benar tombol supaya bisa
        dibuka tanpa JavaScript dan bisa difokus dengan keyboard.

        Kepala halamannya memakai kembaliHref: tombol "Kembali ke Dashboard"
        yang hanya muncul di bawah 768px. Di layar itu sidebar disembunyikan,
        jadi tanpa tombol ini satu-satunya jalan kembali ke Dashboard adalah
        logo di header — dan logo itu terbaca sebagai merek, bukan sebagai
        tombol kembali. Di desktop tombolnya tidak muncul karena sidebar
        sudah menyediakan Dashboard secara terbuka.
    --}}

    <div class="ad-seksi">
        <x-admin.kepala judul="Pengaturan"
            subjudul="Kelola preferensi akun, tampilan, notifikasi, dan aplikasi." ikon="roda"
            :kembaliHref="route('admin.dashboard')" />
    </div>

    <div class="ad-atur-kolom">

        {{-- ==================== KOLOM KIRI ==================== --}}
        <div class="ad-atur-tumpukan">

            {{-- ---------- AKUN ---------- --}}
            <x-admin.atur-seksi judul="Akun" ikon="pengguna"
                subjudul="Kelola informasi profil dan keamanan akun.">

                <x-admin.atur-baris ikon="pengguna" judul="Profil Admin"
                    subjudul="Kelola foto profil, nama, dan email."
                    :href="route('admin.pengaturan.profil')" />

                <button type="button" class="ad-atur-baris" data-atur-dialog-buka="atur-keamanan">
                    <span class="ad-atur-baris__ikon" aria-hidden="true">
                        <x-admin.ikon nama="perisai" ukuran="w-4 h-4" />
                    </span>

                    <span class="ad-atur-baris__teks">
                        <span class="ad-atur-baris__judul">Keamanan</span>
                        <span class="ad-atur-baris__sub">Ubah password dan kelola keamanan akun.</span>
                    </span>

                    <span class="ad-atur-baris__kanan">
                        <x-admin.ikon nama="panah-kanan" ukuran="w-4 h-4"
                            class="ad-atur-baris__chevron" />
                    </span>
                </button>
            </x-admin.atur-seksi>

            {{-- ---------- PREFERENSI ---------- --}}
            <x-admin.atur-seksi judul="Preferensi" ikon="tuas"
                subjudul="Atur tampilan dan notifikasi sesuai kebutuhan.">

                {{--
                    Tampilan tidak berupa baris yang membuka halaman, tapi
                    kendali yang menempel di barisnya. Dua tombol mode
                    dikelompokkan dengan role="group" supaya pembaca layar
                    tahu ini satu kendali dengan dua pilihan, bukan dua
                    kendali yang tidak berhubungan.
                --}}
                <div class="ad-atur-baris">
                    <span class="ad-atur-baris__ikon" aria-hidden="true">
                        <x-admin.ikon nama="monitor" ukuran="w-4 h-4" />
                    </span>

                    <span class="ad-atur-baris__teks">
                        <span class="ad-atur-baris__judul">Tampilan</span>
                        <span class="ad-atur-baris__sub">Pilih mode tampilan aplikasi.</span>
                    </span>

                    <div class="ad-atur-baris__kanan">
                        {{--
                            Satu form untuk dua tombol: tombol yang ditekan
                            mengirim temanya sendiri lewat atribut name dan
                            value, jadi tidak ada input yang perlu diisi lebih
                            dulu dan formnya tetap jalan tanpa JavaScript.

                            JavaScript hanya menambah satu hal: tema langsung
                            berubah sebelum server sempat menjawab, supaya tidak
                            ada kedipan di layar.
                        --}}
                        <form method="POST" action="{{ route('admin.pengaturan.tema') }}">
                            @csrf
                            @method('PUT')

                            <div class="ad-atur-terang-gelap" role="group" aria-label="Mode tampilan"
                                data-atur-tema>
                                <button type="submit" name="tema" value="terang"
                                    class="ad-atur-terang-gelap__tombol" data-atur-tema-pilih="terang"
                                    aria-pressed="{{ $preferensi->temaAman() === 'terang' ? 'true' : 'false' }}">
                                    <x-admin.ikon nama="matahari" ukuran="w-3.5 h-3.5" />
                                    Terang
                                </button>

                                <button type="submit" name="tema" value="gelap"
                                    class="ad-atur-terang-gelap__tombol" data-atur-tema-pilih="gelap"
                                    aria-pressed="{{ $preferensi->temaAman() === 'gelap' ? 'true' : 'false' }}">
                                    <x-admin.ikon nama="bulan" ukuran="w-3.5 h-3.5" />
                                    Gelap
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <button type="button" class="ad-atur-baris" data-atur-dialog-buka="atur-notifikasi">
                    <span class="ad-atur-baris__ikon" aria-hidden="true">
                        <x-admin.ikon nama="lonceng" ukuran="w-4 h-4" />
                    </span>

                    <span class="ad-atur-baris__teks">
                        <span class="ad-atur-baris__judul">Notifikasi</span>
                        <span class="ad-atur-baris__sub">Atur notifikasi yang ingin diterima.</span>
                    </span>

                    <span class="ad-atur-baris__kanan">
                        <x-admin.ikon nama="panah-kanan" ukuran="w-4 h-4"
                            class="ad-atur-baris__chevron" />
                    </span>
                </button>
            </x-admin.atur-seksi>

            {{-- ---------- SISTEM ---------- --}}
            <x-admin.atur-seksi judul="Sistem" ikon="monitor"
                subjudul="Informasi aplikasi dan versi sistem.">

                <x-admin.atur-baris ikon="info" judul="Informasi Sistem"
                    subjudul="Lihat status server, database, dan versi aplikasi."
                    :href="route('admin.pengaturan.sistem')" />

                <x-admin.atur-baris ikon="buku" judul="Tentang Kelas Kita"
                    subjudul="Lihat informasi aplikasi, versi, dan pembaruan."
                    :href="route('admin.pengaturan.tentang')" />
            </x-admin.atur-seksi>
        </div>

        {{-- ==================== KOLOM KANAN ==================== --}}
        <div class="ad-atur-tumpukan">

            {{-- ---------- PENGATURAN PEMBELAJARAN ---------- --}}
            <x-admin.atur-seksi judul="Pengaturan Pembelajaran" ikon="buku"
                subjudul="Kelola mata pelajaran dan pengaturan konten.">

                <x-admin.atur-baris ikon="dokumen" judul="Kelola Mata Pelajaran"
                    subjudul="Atur mata pelajaran yang digunakan."
                    :jumlah="$jumlahPelajaran.' mata pelajaran'"
                    :href="route('admin.pengaturan.pelajaran')" />

                <button type="button" class="ad-atur-baris" data-atur-dialog-buka="atur-publikasi">
                    <span class="ad-atur-baris__ikon" aria-hidden="true">
                        <x-admin.ikon nama="kotak-centang" ukuran="w-4 h-4" />
                    </span>

                    <span class="ad-atur-baris__teks">
                        <span class="ad-atur-baris__judul">Pengaturan Publikasi</span>
                        <span class="ad-atur-baris__sub">Atur default konten baru.</span>
                    </span>

                    <span class="ad-atur-baris__kanan">
                        <x-admin.ikon nama="panah-kanan" ukuran="w-4 h-4"
                            class="ad-atur-baris__chevron" />
                    </span>
                </button>
            </x-admin.atur-seksi>

            {{-- ---------- KEAMANAN LANJUTAN ---------- --}}
            <x-admin.atur-seksi judul="Keamanan Lanjutan" ikon="perisai"
                subjudul="Kelola sesi login dan keamanan akun.">

                <x-admin.atur-baris ikon="ponsel" judul="Sesi Login"
                    subjudul="Lihat perangkat yang sedang login."
                    :href="route('admin.pengaturan.sesi')" />

                <button type="button" class="ad-atur-baris" data-atur-dialog-buka="atur-logout-semua">
                    <span class="ad-atur-baris__ikon" aria-hidden="true">
                        <x-admin.ikon nama="pintu-keluar" ukuran="w-4 h-4" />
                    </span>

                    <span class="ad-atur-baris__teks">
                        <span class="ad-atur-baris__judul">Logout dari Semua Perangkat</span>
                        <span class="ad-atur-baris__sub">Keluar dari semua perangkat yang terhubung.</span>
                    </span>

                    <span class="ad-atur-baris__kanan">
                        <x-admin.ikon nama="panah-kanan" ukuran="w-4 h-4"
                            class="ad-atur-baris__chevron" />
                    </span>
                </button>
            </x-admin.atur-seksi>

            {{--
                Kartu tanpa judul dan tanpa ikon kepala.

                Ikon pintu keluar dan kalimat "Keluar dari Akun" di kepala kartu
                ini hanya mengulang apa yang sudah ada di baris di bawahnya, jadi
                kepala kartanya dihapus. Baris logout-nya tetap ada lengkap
                dengan ikon dan dialognya, jadi keluar dari akun tidak ikut
                hilang.
            --}}
            <x-admin.atur-seksi bahaya>

                <button type="button" class="ad-atur-baris ad-atur-baris--bahaya"
                    data-atur-dialog-buka="atur-keluar">
                    <span class="ad-atur-baris__ikon" aria-hidden="true">
                        <x-admin.ikon nama="pintu-keluar" ukuran="w-4 h-4" />
                    </span>

                    <span class="ad-atur-baris__teks">
                        <span class="ad-atur-baris__judul">Keluar dari Akun</span>
                        <span class="ad-atur-baris__sub">Anda akan keluar dari aplikasi.</span>
                    </span>

                    <span class="ad-atur-baris__kanan">
                        <x-admin.ikon nama="panah-kanan" ukuran="w-4 h-4"
                            class="ad-atur-baris__chevron" />
                    </span>
                </button>
            </x-admin.atur-seksi>
        </div>
    </div>

    {{-- ---------- Dialog-dialog di halaman ini ---------- --}}
    @include('admin.pengaturan.dialog')

    <x-admin.toast :judul="session('sukses')" :pesan="session('suksesDetail')" />

@endsection