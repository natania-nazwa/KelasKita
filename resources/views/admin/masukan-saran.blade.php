@extends('layouts.admin')

@section('title', 'Masukan & Saran | KelasKita')

@section('content')

    {{--
        Halaman "Masukan & Saran" di area admin.

        Ini sisi baca dari halaman kirim milik pengguna: menampilkan seluruh
        kiriman, menyaringnya per status, dan membuka detailnya satu per satu
        lewat dialog. Dari dialog itu admin menandai status tindak lanjutnya.

        Semua angka dan isi dihitung ulang dari database di controller, bukan
        ditulis di markup:

          $statistik  tiga angka utama (total, baru, sudah ditangani);
          $tabStatus  tab filter status lengkap dengan jumlahnya;
          $detail     isi dialog, dikunci dengan id kiriman, dikirim ke
                      JavaScript sebagai satu peta JSON sekaligus.

        Halaman ini tidak menghapus apa pun. Kiriman pengguna adalah catatan
        yang sebaiknya tetap tersimpan, jadi aksi yang tersedia hanya
        perubahan status.
    --}}

    {{-- ==================== KEPALA ==================== --}}
    {{--
        Kepala dibuat di sini, bukan lewat x-admin.kepala, karena halaman ini
        memakai ikon berlingkaran di sebelah judul dan komponen kepala tidak
        merender ikon. Isinya tetap memakai kelas .ad-kepala supaya jarak
        judul dan subjudulnya sama dengan halaman admin lain.
    --}}
    <section class="ad-seksi">
        <div class="ad-kepala">
            <div class="ad-kepala__teks">
                <div class="flex items-start gap-3.5">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-ungu-line bg-ungu-bg text-ungu sm:h-12 sm:w-12"
                        aria-hidden="true">
                        <x-admin.ikon nama="pesan" ukuran="w-5 h-5" :tebal="1.9" />
                    </span>

                    <div class="min-w-0">
                        <h1 class="ad-kepala__judul">Masukan &amp; Saran</h1>

                        <p class="ad-kepala__sub">
                            Kelola seluruh masukan dan saran yang dikirim pengguna, lalu tandai
                            sampai mana tindak lanjutnya.
                        </p>
                    </div>
                </div>
            </div>

            {{--
                Tombol kembali, hanya di layar kecil. Di >= 768px sidebar sudah
                menyediakan Dashboard, jadi tombolnya disembunyikan (md:hidden)
                supaya tidak tampil berdua dengan menu di sidebar; di bawah itu
                inilah jalan pulang yang jelas dari halaman ini. Diletakkan di
                dalam kepala halaman (isi konten), sama seperti Pengaturan.
            --}}
            <a href="{{ route('admin.dashboard') }}"
                class="ad-tombol ad-tombol--garis shrink-0 md:hidden">
                <x-admin.ikon nama="panah-kiri" />

                Kembali ke Dashboard
            </a>
        </div>
    </section>

    {{-- ==================== RINGKASAN ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--statistik" aria-label="Ringkasan masukan">
        <x-admin.statistik ikon="pesan" label="Total Masukan" :nilai="$statistik['total']"
            keterangan="Seluruh kiriman yang tercatat" />

        <x-admin.statistik ikon="jam" label="Masukan Baru" :nilai="$statistik['baru']" nada="kuning"
            keterangan="Belum ditindaklanjuti" />

        <x-admin.statistik ikon="roda" label="Diproses" :nilai="$statistik['diproses']" nada="info"
            keterangan="Sedang ditindaklanjuti" />

        <x-admin.statistik ikon="centang" label="Sudah Ditangani" :nilai="$statistik['selesai']" nada="sukses"
            keterangan="Sudah selesai ditindaklanjuti" />
    </section>

    {{-- ==================== FILTER ==================== --}}
    {{--
        Tab status berupa tautan biasa ke URL yang sudah ada, jadi filter tetap
        jalan tanpa JavaScript dan tombol "kembali" browser tetap mengembalikan
        tab sebelumnya. Form pencarian di sebelah kanan membawa status aktif
        sebagai input tersembunyi supaya mengetik tidak menghapus filter.
    --}}
    <div class="ad-seksi ad-alat">
        <div class="ad-alat__kiri">
            <x-admin.tab :tab="$tabStatus" :aktif="$statusAktif" label="Filter status masukan" />
        </div>

        <form method="GET" action="{{ route('admin.masukan-saran') }}" class="ad-alat__kanan">
            <input type="hidden" name="status" value="{{ $statusAktif }}">

            <div class="ad-cari">
                <label for="q-masukan" class="sr-only">Cari masukan</label>

                <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                <input id="q-masukan" name="q" type="search" value="{{ $kataKunci }}"
                    placeholder="Cari judul atau pengirim..." autocomplete="off">
            </div>

            <button type="submit" class="ad-tombol ad-tombol--garis shrink-0">Cari</button>
        </form>
    </div>

    {{-- ==================== TABEL ==================== --}}
    @if ($daftar->isEmpty())
        <div class="ad-seksi">
            @php
                /*
                 * Pesannya dipilih sesuai keadaan: pencarian yang tidak cocok
                 * berbeda artinya dari tab status yang memang belum berisi.
                 */
                $kosong = filled($kataKunci)
                    ? [
                        'ikon' => 'cari',
                        'judul' => 'Masukan tidak ditemukan',
                        'teks' => 'Tidak ada masukan yang cocok dengan pencarian Anda.',
                    ]
                    : [
                        'ikon' => 'pesan',
                        'judul' => $statusAktif === 'semua' ? 'Belum ada masukan' : 'Belum ada masukan di tab ini',
                        'teks' => $statusAktif === 'semua'
                            ? 'Masukan dari pengguna akan muncul di sini begitu dikirim.'
                            : 'Coba pindah ke tab status yang lain, atau lihat seluruh masukan.',
                    ];
            @endphp

            <x-admin.kosong :ikon="$kosong['ikon']" :judul="$kosong['judul']" :teks="$kosong['teks']" />
        </div>
    @else
        <div class="ad-seksi ad-tabel__bungkus">
            <table class="ad-tabel ad-tabel--masukan">
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Judul Saran</th>
                        <th scope="col">Pengguna</th>
                        <th scope="col">Tanggal Dikirim</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($daftar as $masukan)
                        @php
                            $pengguna = $masukan->pengguna;
                            $avatar = $pengguna?->warnaAvatar();
                            $status = (string) $masukan->status;
                        @endphp

                        <tr>
                            <td class="ad-tabel__nomor" data-label="No">
                                {{ $daftar->firstItem() + $loop->index }}
                            </td>

                            <td data-label="Judul Saran">
                                {{--
                                    Dua baris dalam satu sel: judul di atas,
                                    cuplikan isi di bawahnya. Dibungkus
                                    flex-col supaya keduanya bertumpuk (span
                                    yang bersebelahan akan jatuh sebaris) dan
                                    cuplikannya bisa dipotong dengan elipsis
                                    lewat .ad-tabel__sub.
                                --}}
                                <div class="flex min-w-0 flex-col">
                                    <span class="ad-tabel__judul">{{ $masukan->judul }}</span>

                                    <span class="ad-tabel__sub">
                                        {{ \Illuminate\Support\Str::limit($masukan->detail, 70) }}
                                    </span>
                                </div>
                            </td>

                            <td data-label="Pengguna">
                                @if ($pengguna !== null)
                                    <div class="ad-tabel__nama">
                                        <x-admin.avatar :inisial="$pengguna->inisial()" :warna="$avatar['warna']"
                                            :warna-gelap="$avatar['warna_gelap']" ukuran="kecil" />

                                        <span class="ad-tabel__judul">{{ $pengguna->nama }}</span>
                                    </div>
                                @else
                                    {{-- Baris tetap ada kalau pengirimnya sudah dihapus,
                                         supaya isi masukannya tidak ikut hilang dari pandangan. --}}
                                    <span class="ad-tabel__sub">Pengguna dihapus</span>
                                @endif
                            </td>

                            <td data-label="Tanggal Dikirim">
                                {{-- Sama seperti kolom judul: tanggal di atas,
                                     jam di bawah, supaya tidak jatuh sebaris. --}}
                                <div class="flex min-w-0 flex-col">
                                    <span class="tabular-nums">{{ $masukan->created_at?->translatedFormat('d M Y') }}</span>
                                    <span class="ad-tabel__sub tabular-nums">{{ $masukan->created_at?->format('H:i') }}</span>
                                </div>
                            </td>

                            <td data-label="Status">
                                <span class="ad-lencana ad-lencana--{{ \App\Models\MasukanSaran::nadaStatus($status) }}">
                                    <span class="ad-lencana__titik" aria-hidden="true"></span>
                                    {{ \App\Models\MasukanSaran::labelStatus($status) }}
                                </span>
                            </td>

                            <td data-label="">
                                <div class="ad-tabel__aksi">
                                    <button type="button" class="ad-tombol ad-tombol--halus ad-tombol--kecil"
                                        data-masukan-buka="{{ $masukan->getKey() }}"
                                        aria-label="Lihat detail masukan {{ $masukan->judul }}">
                                        <x-admin.ikon nama="mata" />

                                        Lihat
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
            <p class="ad-ug__info">
                Menampilkan {{ $daftar->firstItem() }}&ndash;{{ $daftar->lastItem() }}
                dari {{ $daftar->total() }} masukan
            </p>

            {{ $daftar->links() }}
        </div>
    @endif

    {{--
        =====================
             DIALOG DETAIL MASUKAN

        Satu dialog untuk semua baris, diisi dari peta $detail oleh
        admin.js. Isinya: judul, isi masukan, identitas pengirim, waktu
        kirim, lampiran (kalau ada), dan pilihan status tindak lanjut.

        Peta JSON-nya ditaruh di <script type="application/json">, bukan di
        atribut data-*, karena isinya terlalu panjang untuk atribut. Tag
        application/json tidak bisa dieksekusi, jadi aman diparse sebagai
        teks.

        Form status di dalam dialog tujuan (action) dan pilihan awalnya
        diatur JavaScript dari peta ini, jadi tidak ada satu form per baris
        yang harus mengulang CSRF-nya masing-masing.
    ====================== --}}
    @if (! $daftar->isEmpty())
        <div class="ad-dialog" data-dialog-masukan role="dialog" aria-modal="true" aria-hidden="true"
            aria-labelledby="dialog-masukan-judul">
            <div class="ad-dialog__kartu">
                <header class="ad-dialog__kepala">
                    <div class="min-w-0 flex-1">
                        <span class="ad-lencana ad-lencana--abu" data-masukan-status>Baru</span>

                        <h2 class="ad-dialog__judul mt-1" id="dialog-masukan-judul" data-masukan-judul></h2>

                        <p class="ad-teks-2 mt-0.5 !text-xs" data-masukan-email></p>
                    </div>

                    <button type="button" class="ad-dialog__tutup" data-masukan-tutup aria-label="Tutup">
                        <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                    </button>
                </header>

                <div class="ad-dialog__badan">
                    <dl class="space-y-1 text-sm">
                        <div class="flex flex-wrap gap-x-2">
                            <dt class="flex items-center gap-1.5 font-semibold text-muted">
                                <x-admin.ikon nama="grup" ukuran="w-3.5 h-3.5" />
                                Pengirim
                            </dt>
                            <dd class="font-semibold text-dark" data-masukan-nama></dd>
                        </div>

                        <div class="flex flex-wrap gap-x-2">
                            <dt class="flex items-center gap-1.5 font-semibold text-muted">
                                <x-admin.ikon nama="kalender" ukuran="w-3.5 h-3.5" />
                                Dikirim
                            </dt>
                            <dd class="tabular-nums text-dark" data-masukan-dikirim></dd>
                        </div>

                        <div class="flex flex-wrap gap-x-2" data-masukan-lampiran-baris hidden>
                            <dt class="flex items-center gap-1.5 font-semibold text-muted">
                                <x-admin.ikon nama="dokumen" ukuran="w-3.5 h-3.5" />
                                Lampiran
                            </dt>
                            <dd>
                                <a href="#" target="_blank" rel="noopener" class="ad-tautan" data-masukan-lampiran>
                                    Buka lampiran
                                    <x-admin.ikon nama="panah-kanan" />
                                </a>
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 border-t border-[color:var(--ad-garis)] pt-4">
                        <p class="ad-ug__aktivitas-label">Isi Masukan</p>

                        {{-- whitespace-pre-wrap: baris baru yang diketik pengguna ikut terbaca. --}}
                        <p class="mt-1.5 whitespace-pre-wrap text-sm leading-relaxed text-dark"
                            data-masukan-detail></p>
                    </div>
                </div>

                <footer class="ad-dialog__kaki flex flex-wrap items-end justify-between gap-3">
                    <form method="POST" action="" class="flex flex-wrap items-end gap-2.5"
                        data-masukan-form-status>
                        @csrf
                        @method('PUT')

                        <div class="ad-field">
                            <label class="ad-field__label" for="masukan-status-pilih">Status tindak lanjut</label>

                            <select id="masukan-status-pilih" name="status" class="ad-pilih"
                                data-masukan-pilih-status>
                                <option value="{{ \App\Models\MasukanSaran::STATUS_BARU }}">Baru</option>
                                <option value="{{ \App\Models\MasukanSaran::STATUS_DIPROSES }}">Diproses</option>
                                <option value="{{ \App\Models\MasukanSaran::STATUS_SELESAI }}">Sudah Ditangani</option>
                            </select>
                        </div>

                        <button type="submit" class="ad-tombol ad-tombol--utama" data-masukan-kirim>
                            <x-admin.ikon nama="centang" />
                            Simpan Status
                        </button>
                    </form>

                    <button type="button" class="ad-tombol ad-tombol--garis" data-masukan-tutup>Tutup</button>
                </footer>
            </div>
        </div>

        <script type="application/json" data-detail-masukan>@json($detail)</script>
    @endif

    <x-admin.toast :judul="session('sukses')" :pesan="session('suksesDetail')" />

@endsection
