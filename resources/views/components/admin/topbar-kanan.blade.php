@props([
    // Akun admin yang sedang login, dipakai lonceng tidak, tapi dipakai
    // menu akun: avatar, nama, dan email.
    'admin',
    // Daftar notifikasi terbaru, sudah dipetakan jadi array polos oleh
    // App\Support\NotifikasiAdmin::daftar(). Bentuknya:
    //   id, jenis, ikon, judul, pesan, tautan, dibaca, waktu, waktu_label
    // Diberikan oleh view composer, bukan oleh halaman pemanggil.
    'notifikasiAdmin' => [],
    // Jumlah notifikasi yang belum dibaca, untuk titik merah di lonceng.
    'notifBelum' => 0,
])

{{--
    Sisi kanan topbar area admin: pencarian, lonceng notifikasi, dan menu akun.

    Dipisah dari layout supaya layout bisapriceda lewat <x-admin.topbar-kanan>
    dan supaya query notifikasinya hanya jalan di halaman yang benar-benar
    menampilkan lonceng. Halaman Pengaturan tidak memakai komponen ini sama
    sekali (lihat $topbarRingkas di layouts/admin), jadi tidak ada satu pun
    query yang dibayar di halaman itu.

    Tanpa JavaScript: pencarian tetap mengirim form, lonceng membuka panel yang
    isinya sudah dirender sejak halaman dimuat, dan menu akun tidak pernah
    terbuka. Yang hilang hanya Membuka panel lonceng dan account, bukan isi
    dari notifikasi itu sendiri.
--}}

@php
    /*
     * Halaman daftar tempat kotak pencarian ini mengirim, beserta teksnya.
     * Dihitung sekali supaya label, placeholder, dan action tidak mungkin
     * berbeda satu sama lain.
     */
    $halamanCari = request()->routeIs('admin.quiz*') ? 'quiz' : 'materi';
@endphp

{{--
    Pencarian topbar. Aplikasi belum punya pencarian global, jadi form ini
    mengirim "q" ke halaman daftar yang sedang dibuka — Materi atau Quiz —
    dan teksnya ikut menyesuaikan halaman itu.

    Teksnya sengaja tidak menjanjikan lebih dari yang ada. Dulu label dan
    placeholder-nya menulis "Cari materi, quiz, pengguna" di setiap halaman,
    padahal form-nya hanya menuju satu daftar: mengetik di halaman Quiz akan
    mendarat di halaman Materi dan hasilnya tidak pernah terlihat. Sekarang
    yang ditulis apa yang benar-benar dicari.

    Halaman lain (Dashboard, Verifikasi, Pengguna, Pengaturan) memakai
    default: Materi, karena itu daftar konten dengan pencarian yang selalu ada.
--}}
<form class="ad-atas__cari" method="GET"
    action="{{ $halamanCari === 'quiz' ? route('admin.quiz') : route('admin.materi') }}" role="search">
    <label for="cari-ad">Cari {{ $halamanCari }}</label>

    <x-admin.ikon nama="cari" class="ad-atas__cari-ikon" />

    <input id="cari-ad" name="q" type="search" value="{{ request('q') }}"
        placeholder="Cari {{ $halamanCari }}..." autocomplete="off">
</form>

<div class="ad-atas__kanan">
    {{--
        Lonceng notifikasi.

        Isi panel sudah dirender server dari tb_notifikasi, jadi notifikasi
        terbaca tanpa JavaScript. Yang ditambahkan JavaScript hanya dua hal:
        membuka panel, dan menandai satu baris terbaca lewat fetch supaya
        titik merahnya hilang sebelum halaman tujuan selesai dimuat.
    --}}
    <div class="ad-atas__notif-wadah" data-admin-notif>
        <button type="button" class="ad-atas__notif" data-admin-notif-tombol aria-expanded="false"
            aria-controls="admin-notif-panel" aria-label="Notifikasi">
            <x-admin.ikon nama="lonceng" ukuran="w-5 h-5" />

            @if ($notifBelum > 0)
                <span class="ad-atas__titik" data-admin-notif-titik aria-hidden="true"></span>
                <span class="sr-only">{{ $notifBelum }} notifikasi belum dibaca</span>
            @endif
        </button>

        <div class="ad-atas__notif-panel" id="admin-notif-panel" data-admin-notif-panel>
            <div class="ad-atas__notif-kepala">
                <span>Notifikasi</span>

                <span class="ad-atas__notif-sisa" data-admin-notif-sisa>
                    @if ($notifBelum > 0)
                        {{ $notifBelum }} belum dibaca
                    @endif
                </span>
            </div>

            <div class="ad-atas__notif-daftar">
                @forelse ($notifikasiAdmin as $item)
                    @php
                        /*
                         * Notifikasi yang kontenh-nya sudah dihapus tidak punya
                         * tautan, jadi barisnya dirender sebagai <div> biasa.
                         * Pakai <a> dengan href kosong akan membuat pembaca
                         * landung di halaman yang sama.
                         */
                        $tag = filled($item['tautan']) ? 'a' : 'div';
                    @endphp

                    <{{ $tag }} @if ($item['tautan']) href="{{ $item['tautan'] }}" @endif
                        class="ad-atas__notif-item @if (! $item['dibaca']) is-belum @endif"
                        data-admin-notif-item
                        data-admin-notif-baca="{{ route('admin.pengaturan.notifikasi.baca', $item['id']) }}">
                        <span class="ad-atas__notif-item-ikon" aria-hidden="true">
                            <x-admin.ikon :nama="$item['ikon']" ukuran="w-4 h-4" />
                        </span>

                        <span class="min-w-0">
                            <span class="ad-atas__notif-item-judul">{{ $item['judul'] }}</span>
                            <span class="ad-atas__notif-item-pesan">{{ $item['pesan'] }}</span>
                            <span class="ad-atas__notif-item-waktu">{{ $item['waktu_label'] }}</span>
                        </span>
                    </{{ $tag }}>
                @empty
                    <p class="ad-atas__notif-kosong">Belum ada notifikasi.</p>
                @endforelse
            </div>

            <p class="ad-atas__notif-kaki">
                Notifikasi dibuat otomatis dari kejadian di aplikasi, dan
                saklarnya ada di <a href="{{ route('admin.pengaturan') }}">Pengaturan</a>.
            </p>
        </div>
    </div>

    {{--
        Menu akun: avatar, nama, peran, arah ke Pengaturan, dan Keluar.

        Tombol Keluar di sini sengaja tetap ada di halaman selain Pengaturan.
        Di Pengaturan topbar ini tidak dirender sama sekali, dan di situ
        Keluar sudah ada di kaki sidebar.
    --}}
    <div class="ad-atas__akun">
        <button type="button" class="ad-atas__akun-tombol" data-akun-tombol aria-expanded="false"
            aria-controls="akun-menu">
            <x-admin.avatar :inisial="$admin?->inisial() ?? 'A'" ukuran="kecil" />

            <span class="ad-atas__akun-teks">
                <span class="ad-atas__akun-nama">{{ $admin?->nama ?? 'Admin' }}</span>
                <span class="ad-atas__akun-peran">Admin</span>
            </span>

            <x-admin.ikon nama="panah-bawah" class="ad-atas__akun-panah" />
        </button>

        <div class="ad-atas__akun-menu" id="akun-menu" data-akun-menu>
            <div class="ad-atas__akun-menu-kepala">
                <x-admin.avatar :inisial="$admin?->inisial() ?? 'A'" ukuran="sedang" />

                <span class="min-w-0">
                    <span class="ad-atas__akun-menu-nama">{{ $admin?->nama ?? 'Admin' }}</span>
                    <span class="ad-atas__akun-menu-email">{{ $admin?->email ?? 'admin@kelaskita.com' }}</span>
                </span>
            </div>

            <a href="{{ route('admin.pengaturan') }}" class="ad-atas__akun-menu-aksi">
                Pengaturan
            </a>

            <form method="POST" action="{{ route('logout') }}" class="ad-atas__akun-menu-kaki">
                @csrf

                <button type="submit" class="ad-atas__akun-menu-aksi">
                    Keluar
                </button>
            </form>
        </div>
    </div>
</div>
