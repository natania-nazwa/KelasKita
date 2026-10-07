@extends('layouts.admin')

@section('title', 'Kelola Mata Pelajaran | KelasKita')

@section('content')

    {{--
        "Kelola Mata Pelajaran" di dalam area Pengaturan admin.

        Mengelola baris tb_pelajaran yang sudah dipakai sebagai filter kategori
        di halaman Materi, Quiz, dan Jadwal. Tabelnya tidak dibuat baru: yang
        diurus di sini adalah baris yang sudah ada, termasuk kolom "aktif"-nya.

        Aturan menghapus versus menonaktifkan:
          - belum dipakai materi atau quiz -> boleh dihapus;
          - sudah dipakai                 -> hapus ditolak, admin diarahkan
            menonaktifkan.

        Nonaktifkan, bukan hapus, penting karena materinya sudah jadi bagian
        dari isi yang dibaca pengguna. Memadamkan barisnya supaya tersembunyi
        dari pilihan konten baru, tanpa merusak apa pun yang sudah terbit.

        Form tambah dan form ubah berbagi satu dialog. Bedanya cuma isian awal
        dan tujuan form-nya, jadi tidak ada dua dialog dengan isi hampir sama.
    --}}

    <div class="ad-seksi">
        <x-admin.kepala judul="Kelola Mata Pelajaran" ikon="dokumen"
            subjudul="Atur mata pelajaran yang digunakan."
            :aksi-teks="'Kembali ke Pengaturan'"
            :aksi-href="route('admin.pengaturan')" />
    </div>

    <div class="ad-atur-lebar">
        <div class="ad-kartu">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon" aria-hidden="true">
                        <x-admin.ikon nama="buku" ukuran="w-5 h-5" />
                    </span>

                    <div class="min-w-0">
                        <h2 class="ad-kartu__kepala-judul">Mata Pelajaran</h2>
                        <p class="ad-kartu__subjudul">
                            {{ $jumlahAktif }} aktif &middot; {{ $jumlahNonaktif }} nonaktif
                        </p>
                    </div>
                </div>

                <button type="button" class="ad-tombol ad-tombol--utama" data-atur-dialog-buka="atur-pelajaran"
                    data-atur-pelajaran-baru>
                    <x-admin.ikon nama="tambah" />
                    Tambah Mata Pelajaran
                </button>
            </header>

            <div class="ad-kartu__badan">
                {{--
                    Kegagalan hapus muncul di sini, bukan di dialog, karena
                    halamannya sudah ditulis ulang setelah form dikirim.
                --}}
                @if (session('galat'))
                    <div class="ad-alert ad-alert--bahaya mb-4" role="alert">
                        <span class="ad-alert__ikon" aria-hidden="true">
                            <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                        </span>

                        <div class="min-w-0">
                            <p class="font-semibold">{{ session('galat') }}</p>

                            @if (filled(session('catatan')))
                                <p class="mt-1">{{ session('catatan') }}</p>
                            @endif
                        </div>
                    </div>
                @endif

                @forelse ($daftar as $pelajaran)
                    <div class="ad-atur-item">
                        <div class="ad-atur-item__teks">
                            <p class="ad-atur-item__judul">{{ $pelajaran->nama }}</p>

                            <p class="ad-atur-item__sub">
                                <span class="ad-atur-kode">{{ $pelajaran->slug }}</span>
                                <span class="mx-1">&middot;</span>
                                {{ $pelajaran->materi_count }} materi
                                <span class="mx-1">&middot;</span>
                                {{ $pelajaran->quiz_count }} quiz
                            </p>
                        </div>

                        <div class="ad-atur-item__aksi">
                            @if ($pelajaran->aktif)
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

                            <button type="button" class="ad-tombol ad-tombol--garis ad-tombol--kecil"
                                data-atur-dialog-buka="atur-pelajaran"
                                data-atur-pelajaran-ubah
                                data-nama="{{ $pelajaran->nama }}"
                                data-slug="{{ $pelajaran->slug }}"
                                data-deskripsi="{{ $pelajaran->deskripsi }}"
                                data-aktif="{{ $pelajaran->aktif ? '1' : '0' }}"
                                data-aksi="{{ route('admin.pengaturan.pelajaran.update', $pelajaran) }}">
                                <x-admin.ikon nama="pena" ukuran="w-3.5 h-3.5" />
                                Edit
                            </button>

                            {{--
                                Nonaktifkan dan aktifkan memakai form POST biasa
                                dengan @method('PUT'), jadi tetap jalan tanpa
                                JavaScript dan bisa diulang lewat keyboard.
                            --}}
                            <form method="POST"
                                action="{{ route('admin.pengaturan.pelajaran.update', $pelajaran) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="nama" value="{{ $pelajaran->nama }}">
                                <input type="hidden" name="aktif"
                                    value="{{ $pelajaran->aktif ? '0' : '1' }}">

                                <button type="submit" class="ad-tombol ad-tombol--halus ad-tombol--kecil">
                                    <x-admin.ikon :nama="$pelajaran->aktif ? 'mata-tutup' : 'mata'"
                                        ukuran="w-3.5 h-3.5" />
                                    {{ $pelajaran->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>

                            @if ($pelajaran->materi_count === 0 && $pelajaran->quiz_count === 0)
                                <button type="button" class="ad-tombol ad-tombol--halus ad-tombol--kecil"
                                    data-hapus-buka
                                    data-hapus-judul="Hapus {{ $pelajaran->nama }}?"
                                    data-hapus-aksi="{{ route('admin.pengaturan.pelajaran.destroy', $pelajaran) }}">
                                    <x-admin.ikon nama="sampah" ukuran="w-3.5 h-3.5" />
                                    Hapus
                                </button>
                            @else
                                {{--
                                    Sudah dipakai: tombol hapus tidak ditampilkan
                                    sama sekali, dan alasannya ditulis supaya
                                    tidak terlihat seperti tombol yang gagal
                                    diklik.
                                --}}
                                <span class="ad-field__petunjuk">Sudah dipakai konten</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-admin.kosong ikon="buku" judul="Belum ada mata pelajaran"
                        teks="Tambahkan mata pelajaran untuk mulai mengatur konten pembelajaran." />

                    <div class="mt-4 text-center">
                        <button type="button" class="ad-tombol ad-tombol--utama"
                            data-atur-dialog-buka="atur-pelajaran" data-atur-pelajaran-baru>
                            <x-admin.ikon nama="tambah" />
                            Tambah Mata Pelajaran
                        </button>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

{{-- ==================== DIALOG TAMBAH / UBAH ==================== --}}
@php($bukaPelajaran = $errors->has(['nama', 'slug', 'deskripsi', 'aktif']))

<div @class(['ad-dialog', 'is-buka' => $bukaPelajaran])
    data-atur-dialog="atur-pelajaran" role="dialog" aria-modal="true"
    aria-hidden="{{ $bukaPelajaran ? 'false' : 'true' }}"
    aria-labelledby="atur-pelajaran-judul">
        <div class="ad-dialog__kartu">
            <header class="ad-dialog__kepala">
                <div class="min-w-0 flex-1">
                    <h2 class="ad-dialog__judul" id="atur-pelajaran-judul" data-atur-pelajaran-judul>
                        Tambah Mata Pelajaran
                    </h2>
                </div>

                <button type="button" class="ad-dialog__tutup" data-atur-dialog-batal aria-label="Tutup">
                    <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                </button>
            </header>

            {{--
                Satu form untuk tambah dan ubah. Tanpa JavaScript, action-nya
                tetap route tambah dan tombol tambah tetap bekerja. Mode ubah
                butuh JavaScript untuk mengganti action dan method-nya.
            --}}
            <form method="POST" action="{{ route('admin.pengaturan.pelajaran.store') }}"
                id="form-atur-pelajaran" data-atur-pelajaran-form>
                @csrf
                <input type="hidden" name="_method" value="POST" data-atur-pelajaran-metode>

                <div class="ad-dialog__badan">
                    @if ($errors->any())
                        <div class="ad-alert ad-alert--bahaya mb-4" role="alert">
                            <span class="ad-alert__ikon" aria-hidden="true">
                                <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                            </span>

                            <div class="min-w-0">
                                <p class="font-semibold">Mata pelajaran belum bisa disimpan:</p>
                                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                                    @foreach ($errors->all() as $pesan)
                                        <li>{{ $pesan }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-4">
                        <label class="ad-field">
                            <span class="ad-field__label">Nama Mata Pelajaran</span>

                            <input class="ad-input" type="text" name="nama"
                                value="{{ old('nama') }}" maxlength="255" required
                                data-atur-pelajaran-nilai="nama">
                        </label>

                        {{--
                            Kolom kode hanya ada di mode tambah. Slug dipakai di
                            URL filter, jadi form ubah sengaja tidak
                            menyentuhnya: mengganti kode akan mematikan tautan
                            lama tanpa disengaja.
                        --}}
                        <label class="ad-field" data-atur-pelajaran-sembunyi>
                            <span class="ad-field__label">Kode Mata Pelajaran</span>

                            <input class="ad-input" type="text" name="slug"
                                value="{{ old('slug') }}" maxlength="255"
                                placeholder="contoh: pemrograman-web" data-atur-pelajaran-nilai="slug">

                            <span class="ad-field__petunjuk">
                                Huruf kecil, angka, dan tanda hubung. Dipakai di URL filter.
                            </span>
                        </label>

                        <label class="ad-field">
                            <span class="ad-field__label">Deskripsi</span>

                            <textarea class="ad-area" name="deskripsi" rows="3" maxlength="1000"
                                data-atur-pelajaran-nilai="deskripsi">{{ old('deskripsi') }}</textarea>
                        </label>

                        <div class="ad-atur-saklar-baris">
                            <input type="hidden" name="aktif" value="0" data-atur-saklar-nilai>

                            <span class="ad-atur-saklar-baris__teks">
                                <span class="ad-atur-saklar-baris__judul">Status Aktif</span>
                                <span class="ad-atur-saklar-baris__sub">
                                    Hanya yang aktif yang muncul sebagai pilihan saat membuat materi atau kuis.
                                </span>
                            </span>

                            <x-admin.atur-saklar judul="Status Aktif" :aktif="old('aktif', '1') === '1'"
                                data-atur-saklar-nama="aktif" />
                        </div>
                    </div>
                </div>

                <footer class="ad-dialog__kaki">
                    <button type="button" class="ad-tombol ad-tombol--garis" data-atur-dialog-batal>Batal</button>

                    <button type="submit" class="ad-tombol ad-tombol--utama">
                        <x-admin.ikon nama="tanda-centang" ukuran="w-4 h-4" :tebal="2.4" />
                        Simpan Mata Pelajaran
                    </button>
                </footer>
            </form>
        </div>
    </div>

    {{-- Dialog hapus memakai komponen yang sudah ada di halaman lain. --}}
    <x-admin.dialog-hapus
        judul="Hapus mata pelajaran?"
        pesan="Mata pelajaran ini belum dipakai materi atau quiz mana pun, jadi aman dihapus." />

    <x-admin.toast :judul="session('sukses')" :pesan="session('suksesDetail')" />

@endsection