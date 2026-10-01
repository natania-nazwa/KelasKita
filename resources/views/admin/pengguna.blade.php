@extends('layouts.admin')

@section('title', 'Pengguna | KelasKita')

@section('content')

    {{--
        Halaman "Pengguna": daftar semua akun yang memakai KelasKita.

        Halaman ini hanya membaca. Tidak ada tombol yang mengubah apa pun:
        tidak ada edit, tidak ada hapus, tidak ada ubah peran. Management
        peran sengaja tidak dibuat karena saat ini hanya ada dua peran
        (ADMIN dan USER) dan hanya ada satu admin, jadi tidak ada apa pun
        yang perlu dikelola.

        Tiga bagian yang dihitung ulang dari database, bukan ditulis di
        markup:

          $statistik  empat angka utama (total, aktif, nonaktif, bergabung
                      hari ini) plus tren 30 hari yang diambil dari helper
                      yang sama dengan dashboard;
          $ringkasan  angka platform yang dulu berdiri sebagai kartu
                      sendiri, sekarang jadi satu baris di kaki tabel;
          $detail     isi dialog detail, dikunci dengan id pengguna.
    --}}

    <x-admin.kepala judul="Pengguna" subjudul="Daftar semua akun yang memakai KelasKita." />

    {{-- ==================== RINGKASAN ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--statistik" aria-label="Ringkasan pengguna">
        {{--
            Kartu "Total Pengguna" satu-satunya yang punya tren, karena hanya
            dia yang punya pembanding: jumlah akun baru dalam 30 hari. Kartu
            aktif dan nonaktif tidak, dan lebih jujur menampilkan keterangan
            daripada persentase yang tidak ada artinya.
        --}}
        <x-admin.statistik ikon="grup" label="Total Pengguna" :nilai="$statistik['total']" nada="info"
            :naik="$statistik['naik']" :keterangan="$statistik['naik'] ? null : $statistik['keterangan']" />

        <x-admin.statistik ikon="centang" label="Akun Aktif" :nilai="$statistik['aktif']" nada="sukses"
            keterangan="Bisa masuk dan memakai platform" />

        <x-admin.statistik ikon="silang" label="Akun Nonaktif" :nilai="$statistik['nonaktif']" nada="merah"
            keterangan="Tidak bisa masuk ke platform" />

        <x-admin.statistik ikon="kalender" label="Bergabung Hari Ini" :nilai="$statistik['bergabung']" nada="kuning"
            :keterangan="now()->translatedFormat('d M Y')" />
    </section>

    {{-- ==================== FILTER ==================== --}}
    {{--
        Dua set tab: peran dan status. Keduanya tautan biasa ke URL yang sudah
        ada, jadi filter tetap jalan tanpa JavaScript dan tombol "kembali"
        browser tetap mengembalikan tab sebelumnya.

        Tabnya tidak memakai satu form bersama karena tab harus bisa diklik
        bergantian dengan filter lain yang sedang aktif. Form di sebelah kanan
        hanya untuk mengetik kata kunci, dan ia membawa nilai peran serta
        status aktif sebagai input tersembunyi supaya mengetik tidak menghapus
        filter yang sedang dipakai.
    --}}
    <div class="ad-seksi ad-alat">
        <div class="ad-alat__kiri">
            <x-admin.tab :tab="$tabPeran" :aktif="$peranAktif" label="Filter peran akun" />

            <x-admin.tab :tab="$tabStatus" :aktif="$statusAktif" label="Filter status akun" />
        </div>

        <form method="GET" action="{{ route('admin.pengguna') }}" class="ad-alat__kanan">
            <input type="hidden" name="peran" value="{{ $peranAktif }}">
            <input type="hidden" name="status" value="{{ $statusAktif }}">

            <div class="ad-cari">
                <label for="q-pengguna" class="sr-only">Cari pengguna</label>

                <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                <input id="q-pengguna" name="q" type="search" value="{{ $kataKunci }}"
                    placeholder="Cari nama atau email..." autocomplete="off">
            </div>

            <button type="submit" class="ad-tombol ad-tombol--garis shrink-0">Cari</button>
        </form>
    </div>

    {{-- ==================== TABEL ==================== --}}
    @if ($daftar->isEmpty())
        <div class="ad-seksi">
            {{--
                Hanya satu keadaan kosong yang bisa benar-benar terjadi di sini.
                Halaman ini hanya terbuka untuk akun yang sudah masuk, jadi
                minimal ada satu baris user di database dan daftar tanpa filter
                aktif tidak mungkin kosong. Kalau kosong berarti besar
                kecenderungannya kata kunci atau tab yang sedang aktif tidak
                cocok dengan data, dan itu yang ditampilkan di bawah.
            --}}
            <x-admin.kosong ikon="cari" judul="Tidak ada pengguna yang cocok"
                teks="Coba kata kunci lain, atau pindah ke tab peran dan status yang lain." />
        </div>
    @else
        <div class="ad-seksi ad-tabel__bungkus">
            <table class="ad-tabel">
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Email</th>
                        <th scope="col">Status</th>
                        <th scope="col">Bergabung</th>
                        <th scope="col"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($daftar as $pengguna)
                        @php $avatar = $pengguna->warnaAvatar(); @endphp

                        <tr>
                            <td class="ad-tabel__nomor" data-label="No">
                                {{ $daftar->firstItem() + $loop->index }}
                            </td>

                            {{--
                                Avatar menempel di kolom nama: kolom ini yang
                                jadi tempat orang mengenali barisnya, jadi
                                foto inisial diletakkan di sebelah nama, bukan
                                di kolom terpisah yang menambah lebar tabel.
                            --}}
                            <td data-label="Nama">
                                <div class="ad-tabel__nama">
                                    <x-admin.avatar :inisial="$pengguna->inisial()" :warna="$avatar['warna']"
                                        :warna-gelap="$avatar['warna_gelap']" ukuran="kecil" />

                                    <span class="ad-tabel__judul">{{ $pengguna->nama }}</span>
                                </div>
                            </td>

                            <td data-label="Email">
                                <span class="ad-tabel__sub">{{ $pengguna->email }}</span>
                            </td>

                            <td data-label="Status">
                                @if ($pengguna->isAktif())
                                    <span class="ad-lencana ad-lencana--sukses">
                                        <span class="ad-lencana__titik" aria-hidden="true"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="ad-lencana ad-lencana--abu">
                                        <span class="ad-lencana__titik" aria-hidden="true"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <td data-label="Bergabung">
                                <span class="tabular-nums">{{ $pengguna->created_at?->translatedFormat('d M Y') }}</span>
                            </td>

                            {{-- data-label kosong: kolom ini tidak punya judul di
                                mobile, jadi labelnya disembunyikan. --}}
                            <td data-label="">
                                <div class="ad-tabel__aksi">
                                    <button type="button" class="ad-tombol ad-tombol--halus ad-tombol--kecil"
                                        data-detail-buka="{{ $pengguna->getKey() }}"
                                        aria-label="Lihat detail {{ $pengguna->nama }}">
                                        <x-admin.ikon nama="mata" />

                                        Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ==================== KAKI DAFTAR ==================== --}}
        <div class="ad-seksi ad-paginasi">
            {{--
                Baris informasi di kaki daftar. Dua angka platform ini dulu
                berdiri sendiri sebagai kartu, dan sengaja tetap ada: jumlah
                karya per pengguna sudah ada di tabel, tapi "berapa karya yang
                ada di platform ini" dan "berapa akun yang mengurus konten"
                hanya berarti sebagai satu angka untuk seluruh halaman.
            --}}
            <p class="ad-ug__info">
                Menampilkan {{ $daftar->firstItem() }}&ndash;{{ $daftar->lastItem() }}
                dari {{ $daftar->total() }} pengguna
                <span class="ad-ug__info-titik" aria-hidden="true">·</span>
                {{ $ringkasan['karya'] }} karya di platform
                <span class="ad-ug__info-titik" aria-hidden="true">·</span>
                rata-rata {{ $ringkasan['rata_karya'] }} karya per pengguna
                <span class="ad-ug__info-titik" aria-hidden="true">·</span>
                {{ $ringkasan['admin'] }} admin
            </p>

            {{ $daftar->links() }}
        </div>
    @endif

    {{--
        =====================
             DIALOG DETAIL PENGGUNA

        Satu dialog untuk semua baris, diisi dari peta $detail di halaman ini
        oleh admin.js. Jadi daftar yang panjang tetap hanya punya satu kotak
        detail, dan tidak ada satu pun baris yang menulis angka statistiknya
        sendiri di markup.

        Peta JSON-nya ditaruh di <script type="application/json">, bukan di
        atribut data-* seperti dialog hapus: isinya terlalu panjang untuk
        atribut, dan JSON.parse di dalam atribut itu berulang di setiap
        baris. Tag application/json juga tidak bisa dieksekusi, jadi isinya
        aman diparse sebagai teks.

        Tanpa JavaScript, tombolnya tidak melakukan apa-apa. Tidak apa-apa,
        karena tabelnya tetap lengkap untuk dibaca: kolom Nama, Email,
        Status dan Bergabung selalu tertulis di baris tabel. Angka karya
        dan aktivitas memang hanya ada di dialog ini, tapi itu data
        tambahan, bukan satu-satunya jalan ke data yang ada di halaman.
    ====================== --}}
    @if (! $daftar->isEmpty())
        <div class="ad-dialog" data-dialog-pengguna role="dialog" aria-modal="true" aria-hidden="true"
            aria-labelledby="dialog-pengguna-nama">
            <div class="ad-dialog__kartu">
                <header class="ad-dialog__kepala">
                    <div class="min-w-0 flex-1">
                        <p class="ad-ug__dialog-peran" data-detail-peran>User</p>

                        <h2 class="ad-dialog__judul" id="dialog-pengguna-nama" data-detail-nama></h2>

                        <p class="ad-teks-2 mt-0.5 !text-xs" data-detail-email></p>
                    </div>

                    <button type="button" class="ad-dialog__tutup" data-detail-tutup aria-label="Tutup">
                        <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                    </button>
                </header>

                <div class="ad-dialog__badan">
                    <div class="ad-ug__identitas">
                        <span class="ad-avatar ad-avatar--besar" data-detail-avatar style="--a: #8b7bf0; --a-gelap: #5b46cf;"></span>

                        <dl class="ad-ug__identitas-daftar">
                            <div class="ad-ug__identitas-baris">
                                <dt>
                                    <x-admin.ikon nama="perisai" ukuran="w-3.5 h-3.5" />
                                    Peran
                                </dt>
                                <dd><span class="ad-lencana ad-lencana--abu" data-detail-peran-lencana>User</span></dd>
                            </div>

                            <div class="ad-ug__identitas-baris">
                                <dt>
                                    <x-admin.ikon nama="centang" ukuran="w-3.5 h-3.5" />
                                    Status
                                </dt>
                                <dd><span class="ad-lencana ad-lencana--abu" data-detail-status>Aktif</span></dd>
                            </div>

                            <div class="ad-ug__identitas-baris">
                                <dt>
                                    <x-admin.ikon nama="kalender" ukuran="w-3.5 h-3.5" />
                                    Bergabung
                                </dt>
                                <dd><span class="tabular-nums" data-detail-bergabung></span></dd>
                            </div>
                        </dl>
                    </div>

                    {{--
                        Empat kartu kecil. Semuanya angka nyata dari $detail:
                        tidak ada placeholder, dan tidak ada nol untuk data
                        yang belum ada. "Quiz diselesaikan" sengaja menghitung
                        pengerjaan yang sudah ditutup, sama dengan definisi
                        PengerjaanQuiz::scopeSelesai().
                    --}}
                    <div class="ad-ug__aktivitas">
                        <div class="ad-ug__aktivitas-kartu">
                            <span class="ad-ug__aktivitas-ikon">
                                <x-admin.ikon nama="buku" ukuran="w-4 h-4" />
                            </span>

                            <span class="ad-ug__aktivitas-isi">
                                <span class="ad-ug__aktivitas-label">Materi dibuat</span>
                                <span class="ad-ug__aktivitas-nilai tabular-nums" data-detail-materi>0</span>
                            </span>
                        </div>

                        <div class="ad-ug__aktivitas-kartu">
                            <span class="ad-ug__aktivitas-ikon">
                                <x-admin.ikon nama="soal" ukuran="w-4 h-4" />
                            </span>

                            <span class="ad-ug__aktivitas-isi">
                                <span class="ad-ug__aktivitas-label">Quiz dibuat</span>
                                <span class="ad-ug__aktivitas-nilai tabular-nums" data-detail-quiz>0</span>
                            </span>
                        </div>

                        <div class="ad-ug__aktivitas-kartu">
                            <span class="ad-ug__aktivitas-ikon">
                                <x-admin.ikon nama="piala" ukuran="w-4 h-4" />
                            </span>

                            <span class="ad-ug__aktivitas-isi">
                                <span class="ad-ug__aktivitas-label">Quiz diselesaikan</span>
                                <span class="ad-ug__aktivitas-nilai tabular-nums" data-detail-selesai>0</span>
                            </span>
                        </div>

                        <div class="ad-ug__aktivitas-kartu">
                            <span class="ad-ug__aktivitas-ikon">
                                <x-admin.ikon nama="grafik" ukuran="w-4 h-4" />
                            </span>

                            <span class="ad-ug__aktivitas-isi">
                                <span class="ad-ug__aktivitas-label">Rata-rata nilai</span>
                                <span class="ad-ug__aktivitas-nilai tabular-nums" data-detail-nilai>&mdash;</span>
                                <span class="ad-ug__aktivitas-ket" data-detail-nilai-ket>Belum ada quiz yang selesai</span>
                            </span>
                        </div>
                    </div>
                </div>

                <footer class="ad-dialog__kaki">
                    <button type="button" class="ad-tombol ad-tombol--garis" data-detail-tutup>Tutup</button>
                </footer>
            </div>
        </div>

        <script type="application/json" data-detail-pengguna>@json($detail)</script>
    @endif

@endsection
